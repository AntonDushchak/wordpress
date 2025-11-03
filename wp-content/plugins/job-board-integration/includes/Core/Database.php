<?php

namespace NeoJobBoard\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Database {
    
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_templates = $wpdb->prefix . 'neo_job_board_templates';
        $sql_templates = "CREATE TABLE $table_templates (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            fields longtext NOT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_by int(11) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_created_by (created_by)
        ) $charset_collate;";

        $table_applications = $wpdb->prefix . 'neo_job_board_applications';
        $sql_applications = "CREATE TABLE $table_applications (
            id int(11) NOT NULL AUTO_INCREMENT,
            hash_id varchar(32) NOT NULL UNIQUE,
            template_id int(11) NOT NULL,
            position varchar(255),
            responsible_employee int(11) DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            is_called tinyint(1) DEFAULT 0,
            application_data longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_hash_id (hash_id),
            KEY idx_template_id (template_id),
            KEY idx_responsible_employee (responsible_employee),
            KEY idx_created_at (created_at),
            KEY idx_is_called (is_called)
        ) $charset_collate;";

        $table_application_data = $wpdb->prefix . 'neo_job_board_application_data';
        $sql_application_data = "CREATE TABLE $table_application_data (
            id int(11) NOT NULL AUTO_INCREMENT,
            application_id int(11) NOT NULL,
            field_name varchar(100) NOT NULL,
            field_value longtext,
            field_type varchar(50) DEFAULT 'text',
            is_personal tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_application_id (application_id),
            KEY idx_field_name (field_name)
        ) $charset_collate;";

        $table_application_details = $wpdb->prefix . 'neo_job_board_application_details';
        $sql_application_details = "CREATE TABLE $table_application_details (
            id int(11) NOT NULL AUTO_INCREMENT,
            application_id int(11) NOT NULL,
            field_name varchar(100) NOT NULL,
            field_value longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_application_id (application_id),
            KEY idx_field_name (field_name)
        ) $charset_collate;";

        $table_files = $wpdb->prefix . 'neo_job_board_files';
        $sql_files = "CREATE TABLE $table_files (
            id int(11) NOT NULL AUTO_INCREMENT,
            application_id int(11) NOT NULL,
            field_name varchar(100) NOT NULL,
            original_name varchar(255) NOT NULL,
            file_path varchar(500) NOT NULL,
            file_size int(11) DEFAULT 0,
            file_type varchar(100),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_application_id (application_id)
        ) $charset_collate;";

        $table_api_logs = $wpdb->prefix . 'neo_job_board_api_logs';
        $sql_api_logs = "CREATE TABLE $table_api_logs (
            id int(11) NOT NULL AUTO_INCREMENT,
            template_id int(11),
            action varchar(50) NOT NULL,
            endpoint varchar(255) NOT NULL,
            method varchar(10) NOT NULL,
            request_data longtext,
            response_data longtext,
            status_code int(11),
            success tinyint(1) DEFAULT 0,
            error_message text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_template_id (template_id),
            KEY idx_action (action),
            KEY idx_success (success),
            KEY idx_created_at (created_at)
        ) $charset_collate;";

        $table_contact_requests = $wpdb->prefix . 'neo_job_board_contact_requests';
        $sql_contact_requests = "CREATE TABLE $table_contact_requests (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            application_hash varchar(8) NOT NULL,
            application_id int(11) DEFAULT NULL,
            name varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            phone varchar(50) DEFAULT NULL,
            message longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_application_hash (application_hash),
            KEY idx_application_id (application_id),
            KEY idx_created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_templates);
        dbDelta($sql_applications);
        dbDelta($sql_application_data);
        dbDelta($sql_application_details);
        dbDelta($sql_files);
        dbDelta($sql_api_logs);
        dbDelta($sql_contact_requests);
    }

    public static function migrate_add_is_called_column() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'neo_job_board_applications';
        
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = %s 
             AND TABLE_NAME = %s 
             AND COLUMN_NAME = 'is_called'",
            DB_NAME,
            $table_name
        ));
        
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN is_called tinyint(1) DEFAULT 0");
            $wpdb->query("ALTER TABLE $table_name ADD INDEX idx_is_called (is_called)");
        }
    }
    
    public static function migrate_remove_columns() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'neo_job_board_applications';
        
        $columns_to_drop = ['first_name', 'last_name', 'email', 'phone', 'status'];
        
        foreach ($columns_to_drop as $column) {
            $column_exists = $wpdb->get_results($wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = %s 
                 AND TABLE_NAME = %s 
                 AND COLUMN_NAME = %s",
                DB_NAME,
                $table_name,
                $column
            ));
            
            if (!empty($column_exists)) {
                $wpdb->query("ALTER TABLE $table_name DROP COLUMN $column");
            }
        }
        
        $index_exists = $wpdb->get_results($wpdb->prepare(
            "SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS 
             WHERE TABLE_SCHEMA = %s 
             AND TABLE_NAME = %s 
             AND INDEX_NAME = 'idx_status'",
            DB_NAME,
            $table_name
        ));
        
        if (!empty($index_exists)) {
            $wpdb->query("ALTER TABLE $table_name DROP INDEX idx_status");
        }
        
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = %s 
             AND TABLE_NAME = %s 
             AND COLUMN_NAME = 'responsible_employee'
             AND DATA_TYPE = 'varchar'",
            DB_NAME,
            $table_name
        ));
        
        if (!empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_name MODIFY COLUMN responsible_employee int(11) DEFAULT NULL");
            $index_check = $wpdb->get_results($wpdb->prepare(
                "SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS 
                 WHERE TABLE_SCHEMA = %s 
                 AND TABLE_NAME = %s 
                 AND INDEX_NAME = 'idx_responsible_employee'",
                DB_NAME,
                $table_name
            ));
            
            if (empty($index_check)) {
                $wpdb->query("ALTER TABLE $table_name ADD INDEX idx_responsible_employee (responsible_employee)");
            }
        }
    }

    public static function generate_unique_hash() {
        global $wpdb;
        $attempts = 0;
        $max_attempts = 10;

        do {
            $hash = substr(md5(uniqid((string)rand(), true)), 0, 32);
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}neo_job_board_applications WHERE hash_id = %s",
                $hash
            ));
            $attempts++;
        } while ($exists > 0 && $attempts < $max_attempts);

        return $hash;
    }
}

