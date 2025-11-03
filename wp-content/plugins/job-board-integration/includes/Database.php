<?php

namespace NeoJobBoard;

if (!defined('ABSPATH')) {
    exit;
}

class Database {
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $wpdb->hide_errors();

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
            application_data longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_hash_id (hash_id),
            KEY idx_template_id (template_id),
            KEY idx_responsible_employee (responsible_employee),
            KEY idx_created_at (created_at)
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
            KEY idx_field_name (field_name),
            FOREIGN KEY (application_id) REFERENCES $table_applications(id) ON DELETE CASCADE
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
            KEY idx_application_id (application_id),
            FOREIGN KEY (application_id) REFERENCES $table_applications(id) ON DELETE CASCADE
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
            KEY idx_field_name (field_name),
            FOREIGN KEY (application_id) REFERENCES $table_applications(id) ON DELETE CASCADE
        ) $charset_collate;";

        $table_job_applications = $wpdb->prefix . 'neo_job_applications';
        $sql_job_applications = "CREATE TABLE $table_job_applications (
            id int(11) NOT NULL AUTO_INCREMENT,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(20),
            address text,
            desired_position varchar(255) NOT NULL,
            salary_expectation varchar(100),
            availability_type varchar(20),
            availability_date date,
            cover_letter longtext,
            experience longtext,
            education longtext,
            languages longtext,
            status enum('new', 'reviewing', 'interview', 'hired', 'rejected') DEFAULT 'new',
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(45),
            user_agent text,
            api_sent tinyint(1) DEFAULT 0,
            api_response text,
            PRIMARY KEY (id),
            KEY idx_email (email),
            KEY idx_status (status),
            KEY idx_submitted_at (submitted_at),
            KEY idx_desired_position (desired_position)
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

        $table_personal_data_logs = $wpdb->prefix . 'neo_job_board_personal_data_log';
        $sql_personal_data_logs = "CREATE TABLE $table_personal_data_logs (
            id int(11) NOT NULL AUTO_INCREMENT,
            action varchar(50) NOT NULL,
            data_id int(11) NOT NULL,
            user_id int(11) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            details text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_action (action),
            KEY idx_data_id (data_id),
            KEY idx_user_id (user_id),
            KEY idx_created_at (created_at)
        ) $charset_collate;";

        $table_found_jobs = $wpdb->prefix . 'neo_job_board_found_jobs';
        $sql_found_jobs = "CREATE TABLE $table_found_jobs (
            id int(11) NOT NULL AUTO_INCREMENT,
            application_id int(11) NOT NULL,
            application_hash varchar(32),
            found_date datetime DEFAULT CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_application_id (application_id),
            KEY idx_found_date (found_date)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        self::create_table_if_not_exists($table_templates, $sql_templates);
        self::create_table_if_not_exists($table_applications, $sql_applications);
        self::create_table_if_not_exists($table_application_details, $sql_application_details);
        self::create_table_if_not_exists($table_application_data, $sql_application_data);
        self::create_table_if_not_exists($table_files, $sql_files);
        self::create_table_if_not_exists($table_job_applications, $sql_job_applications);
        self::create_table_if_not_exists($table_api_logs, $sql_api_logs);
        self::create_table_if_not_exists($table_personal_data_logs, $sql_personal_data_logs);
        self::create_table_if_not_exists($table_found_jobs, $sql_found_jobs);
        

        self::migrate_add_is_active_field();
        
        $wpdb->show_errors();

        $upload_dir = wp_upload_dir();
        $neo_upload_dir = $upload_dir['basedir'] . '/neo-job-board';
        if (!file_exists($neo_upload_dir)) {
            wp_mkdir_p($neo_upload_dir);
            $htaccess_content = "Options -Indexes\n<Files *.php>\nDeny from all\n</Files>";
            file_put_contents($neo_upload_dir . '/.htaccess', $htaccess_content);
        }
    }

    private static function create_table_if_not_exists($table_name, $sql) {
        global $wpdb;
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            ob_start();
            dbDelta($sql);
            ob_end_clean();
        }
    }

    public static function migrate_add_is_active_field() {
        global $wpdb;
        
        $table_applications = $wpdb->prefix . 'neo_job_board_applications';
        
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SHOW COLUMNS FROM {$table_applications} LIKE %s",
            'is_active'
        ));
        
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$table_applications} ADD COLUMN is_active tinyint(1) DEFAULT 1 AFTER status");
            
            $wpdb->query("UPDATE {$table_applications} SET is_active = 1 WHERE is_active IS NULL");
        }
    }

    public static function migrate_legacy_data() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'neo_job_board_applications';

        $has_hash_id = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'hash_id'");
        
        if (!$has_hash_id) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN hash_id varchar(32) AFTER id");
            
            $applications = $wpdb->get_results("SELECT id FROM $table_name WHERE hash_id IS NULL OR hash_id = ''");
            
            foreach ($applications as $app) {
                $hash_id = md5(uniqid((string)$app->id, true));
                $wpdb->update(
                    $table_name,
                    ['hash_id' => $hash_id],
                    ['id' => $app->id]
                );
            }
            
            $wpdb->query("ALTER TABLE $table_name ADD UNIQUE KEY idx_hash_id (hash_id)");
        }

        $has_application_data = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'application_data'");
        
        if (!$has_application_data) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN application_data longtext DEFAULT NULL AFTER status");
        }

        $indexes = $wpdb->get_results("SHOW INDEX FROM $table_name");
        $existing_indexes = array_column($indexes, 'Key_name');

        if (!in_array('idx_template_id', $existing_indexes)) {
            $wpdb->query("ALTER TABLE $table_name ADD KEY idx_template_id (template_id)");
        }
        if (!in_array('idx_status', $existing_indexes)) {
            $wpdb->query("ALTER TABLE $table_name ADD KEY idx_status (status)");
        }
        if (!in_array('idx_created_at', $existing_indexes)) {
            $wpdb->query("ALTER TABLE $table_name ADD KEY idx_created_at (created_at)");
        }

        $templates_table = $wpdb->prefix . 'neo_job_board_templates';
        $templates_exists = $wpdb->get_var("SHOW TABLES LIKE '$templates_table'") === $templates_table;
        
        if ($templates_exists) {
            $columns = $wpdb->get_col("DESC $templates_table", 0);
            
            if (!in_array('description', $columns)) {
                $wpdb->query("ALTER TABLE $templates_table ADD COLUMN description text AFTER name");
            }
            if (!in_array('is_active', $columns)) {
                $wpdb->query("ALTER TABLE $templates_table ADD COLUMN is_active tinyint(1) DEFAULT 1 AFTER fields");
            }
            if (!in_array('created_by', $columns)) {
                $wpdb->query("ALTER TABLE $templates_table ADD COLUMN created_by int(11) DEFAULT NULL AFTER is_active");
                $wpdb->query("ALTER TABLE $templates_table ADD KEY idx_created_by (created_by)");
            }
            if (!in_array('created_at', $columns)) {
                $wpdb->query("ALTER TABLE $templates_table ADD COLUMN created_at datetime DEFAULT CURRENT_TIMESTAMP AFTER created_by");
            }
            if (!in_array('updated_at', $columns)) {
                $wpdb->query("ALTER TABLE $templates_table ADD COLUMN updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
            }
        }

        self::create_default_template();
    }

    public static function create_default_template() {
        global $wpdb;
        
        $templates_table = $wpdb->prefix . 'neo_job_board_templates';
        
        $existing_templates = $wpdb->get_var("SELECT COUNT(*) FROM $templates_table");
        
        if ($existing_templates == 0) {
            $default_fields = [
                [
                    'label' => 'Name (Vor- und Nachname)',
                    'type' => 'text',
                    'required' => true,
                    'is_personal' => true,
                    'name' => 'name',
                    'field_name' => 'name',
                    'personal_data' => true
                ],
                [
                    'label' => 'Email',
                    'type' => 'email',
                    'required' => true,
                    'is_personal' => true,
                    'personal_data' => true
                ],
                [
                    'label' => 'Telefon',
                    'type' => 'tel',
                    'required' => false,
                    'is_personal' => true,
                    'personal_data' => true
                ],
                [
                    'label' => 'Position',
                    'type' => 'text',
                    'required' => false,
                    'is_personal' => false,
                    'personal_data' => false
                ],
                [
                    'label' => 'Berufserfahrung',
                    'type' => 'textarea',
                    'required' => false,
                    'is_personal' => false,
                    'personal_data' => false
                ]
            ];

            $wpdb->insert(
                $templates_table,
                [
                    'name' => 'Standard-Bewerbungsformular',
                    'description' => 'Standardformular mit den wichtigsten Feldern für die Personalauswahl',
                    'fields' => json_encode($default_fields),
                    'is_active' => 1,
                    'created_by' => 1,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ]
            );
        }
    }
}