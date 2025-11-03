<?php

namespace NeoJobBoard\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Sync {
    
    public static function sync_contact_requests() {
        $api_url = get_option('jbi_api_url');
        
        if (empty($api_url)) {
            error_log('JBI Sync: API URL не настроен');
            return;
        }

        $base_url = rtrim($api_url, '/');
        if (strpos($base_url, '/wp-json/bewerberboerse/v1') !== false) {
            $api_base = substr($base_url, 0, strpos($base_url, '/wp-json/bewerberboerse/v1'));
        } else {
            $api_base = $base_url;
        }
        
        $check_endpoint = $api_base . '/wp-json/bewerberboerse/v1/contact-requests/check';

        $check_response = wp_remote_get($check_endpoint, [
            'timeout' => 30,
            'sslverify' => false
        ]);

        if (is_wp_error($check_response)) {
            error_log('JBI Sync: Ошибка проверки наличия данных - ' . $check_response->get_error_message());
            return;
        }

        $check_code = wp_remote_retrieve_response_code($check_response);
        if ($check_code !== 200) {
            error_log('JBI Sync: Неверный код ответа при проверке - ' . $check_code);
            return;
        }

        $check_body = wp_remote_retrieve_body($check_response);
        $check_data = json_decode($check_body, true);

        if (!isset($check_data['has_data']) || !$check_data['has_data']) {
            return;
        }

        $limit = $check_data['count'] ?? 100;
        $fetch_endpoint = $api_base . '/wp-json/bewerberboerse/v1/contact-requests?limit=' . $limit;

        $fetch_response = wp_remote_get($fetch_endpoint, [
            'timeout' => 30,
            'sslverify' => false
        ]);

        if (is_wp_error($fetch_response)) {
            error_log('JBI Sync: Ошибка получения данных - ' . $fetch_response->get_error_message());
            return;
        }

        $fetch_code = wp_remote_retrieve_response_code($fetch_response);
        if ($fetch_code !== 200) {
            error_log('JBI Sync: Неверный код ответа при получении данных - ' . $fetch_code);
            return;
        }

        $fetch_body = wp_remote_retrieve_body($fetch_response);
        $contact_requests = json_decode($fetch_body, true);

        if (!is_array($contact_requests) || empty($contact_requests)) {
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'neo_job_board_contact_requests';
        $applications_table = $wpdb->prefix . 'neo_job_board_applications';
        
        $saved_count = 0;

        foreach ($contact_requests as $request) {
            $application_hash = sanitize_text_field($request['application_hash'] ?? '');
            $name = sanitize_text_field($request['name'] ?? '');
            $email = sanitize_email($request['email'] ?? '');
            $phone = isset($request['phone']) ? sanitize_text_field($request['phone']) : null;
            $message = isset($request['message']) ? sanitize_textarea_field($request['message']) : null;
            $created_at = isset($request['created_at']) ? sanitize_text_field($request['created_at']) : current_time('mysql');

            if (empty($application_hash) || empty($name) || empty($email)) {
                continue;
            }

            $application_id = null;
            if (!empty($application_hash)) {
                $application = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM $applications_table WHERE hash_id LIKE %s LIMIT 1",
                    $application_hash . '%'
                ));
                
                if ($application) {
                    $application_id = $application->id;
                    $wpdb->update(
                        $applications_table,
                        ['is_called' => 1],
                        ['id' => $application_id],
                        ['%d'],
                        ['%d']
                    );
                }
            }

            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_name WHERE application_hash = %s AND email = %s AND created_at = %s",
                $application_hash,
                $email,
                $created_at
            ));

            if ($existing) {
                continue;
            }

            $result = $wpdb->insert(
                $table_name,
                [
                    'application_hash' => $application_hash,
                    'application_id' => $application_id,
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'message' => $message,
                    'created_at' => $created_at
                ],
                ['%s', '%d', '%s', '%s', '%s', '%s', '%s']
            );

            if ($result !== false) {
                $saved_count++;
            }
        }

        if ($saved_count > 0) {
            $delete_endpoint = $api_base . '/wp-json/bewerberboerse/v1/contact-requests/delete-all';

            $delete_response = wp_remote_request($delete_endpoint, [
                'method' => 'DELETE',
                'timeout' => 30,
                'sslverify' => false
            ]);

            if (!is_wp_error($delete_response)) {
                $delete_code = wp_remote_retrieve_response_code($delete_response);
                if ($delete_code === 200) {
                    error_log("JBI Sync: Успешно синхронизировано $saved_count контактных запросов и удалено на удаленном сайте");
                } else {
                    error_log("JBI Sync: Сохранено $saved_count запросов, но не удалось удалить на удаленном сайте (код: $delete_code)");
                }
            } else {
                error_log("JBI Sync: Сохранено $saved_count запросов, но ошибка при удалении: " . $delete_response->get_error_message());
            }
        }
    }
}

