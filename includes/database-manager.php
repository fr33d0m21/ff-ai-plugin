<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class FFAI_Database_Manager {

    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Crop Yields Table
        $table_name = $wpdb->prefix . 'ffai_crop_yields';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            crop varchar(100) NOT NULL,
            year int(4) NOT NULL,
            yield float NOT NULL,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY crop (crop),
            KEY year (year)
        ) $charset_collate;";
        dbDelta($sql);

        // IoT Devices Table
        $table_name = $wpdb->prefix . 'ffai_iot_devices';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            device_id varchar(100) NOT NULL,
            device_type varchar(50) NOT NULL,
            location varchar(255),
            last_sync datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY device_id (device_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        dbDelta($sql);

        // IoT Sensor Data Table
        $table_name = $wpdb->prefix . 'ffai_iot_sensor_data';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            device_id varchar(100) NOT NULL,
            sensor_type varchar(50) NOT NULL,
            value float NOT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY device_id (device_id),
            KEY sensor_type (sensor_type),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        dbDelta($sql);

        // Weather Data Table
        $table_name = $wpdb->prefix . 'ffai_weather_data';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            location varchar(255) NOT NULL,
            date date NOT NULL,
            temperature float,
            humidity float,
            rainfall float,
            wind_speed float,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY location_date (location, date),
            KEY date (date)
        ) $charset_collate;";
        dbDelta($sql);

        // AI Interactions Table
        $table_name = $wpdb->prefix . 'ffai_ai_interactions';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            query text NOT NULL,
            response text NOT NULL,
            intent varchar(50),
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY intent (intent),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        dbDelta($sql);

        // Resources Table
        $table_name = $wpdb->prefix . 'ffai_resources';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            content longtext NOT NULL,
            category varchar(100),
            tags text,
            author bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY category (category),
            FULLTEXT KEY content (title, content)
        ) $charset_collate;";
        dbDelta($sql);

        update_option('ffai_db_version', FFAI_VERSION);
    }

    public static function update_tables() {
        $current_version = get_option('ffai_db_version', '0');
        if (version_compare($current_version, FFAI_VERSION, '<')) {
            self::create_tables();
        }
    }

    public static function insert($table, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ffai_' . $table;
        
        $result = $wpdb->insert($table_name, $data);
        if ($result === false) {
            return new WP_Error('db_insert_error', $wpdb->last_error);
        }
        return $wpdb->insert_id;
    }

    public static function update($table, $data, $where) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ffai_' . $table;
        
        $result = $wpdb->update($table_name, $data, $where);
        if ($result === false) {
            return new WP_Error('db_update_error', $wpdb->last_error);
        }
        return $result;
    }

    public static function delete($table, $where) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ffai_' . $table;
        
        $result = $wpdb->delete($table_name, $where);
        if ($result === false) {
            return new WP_Error('db_delete_error', $wpdb->last_error);
        }
        return $result;
    }

    public static function get_results($table, $query = '', $output = OBJECT) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ffai_' . $table;
        
        if (empty($query)) {
            $query = "SELECT * FROM $table_name";
        } else {
            $query = str_replace('{table}', $table_name, $query);
        }
        
        return $wpdb->get_results($query, $output);
    }

    public static function get_row($table, $query = '', $output = OBJECT) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ffai_' . $table;
        
        if (empty($query)) {
            $query = "SELECT * FROM $table_name LIMIT 1";
        } else {
            $query = str_replace('{table}', $table_name, $query);
        }
        
        return $wpdb->get_row($query, $output);
    }

    public static function get_var($table, $query) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ffai_' . $table;
        
        $query = str_replace('{table}', $table_name, $query);
        return $wpdb->get_var($query);
    }

    public static function query($query) {
        global $wpdb;
        return $wpdb->query($query);
    }

    public static function prepare($query, $args) {
        global $wpdb;
        return $wpdb->prepare($query, $args);
    }
}

// Add custom WP-CLI commands for database management
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('ffai db create', function($args) {
        FFAI_Database_Manager::create_tables();
        WP_CLI::success('Database tables created successfully.');
    });

    WP_CLI::add_command('ffai db update', function($args) {
        FFAI_Database_Manager::update_tables();
        WP_CLI::success('Database tables updated successfully.');
    });

    WP_CLI::add_command('ffai db insert', function($args, $assoc_args) {
        if (count($args) < 1) {
            WP_CLI::error('Please specify a table name.');
            return;
        }
        $table = $args[0];
        $data = $assoc_args;
        $result = FFAI_Database_Manager::insert($table, $data);
        if (is_wp_error($result)) {
            WP_CLI::error($result->get_error_message());
        } else {
            WP_CLI::success("Data inserted successfully. Insert ID: $result");
        }
    });

    WP_CLI::add_command('ffai db query', function($args) {
        if (count($args) < 1) {
            WP_CLI::error('Please provide a SQL query.');
            return;
        }
        $query = $args[0];
        $result = FFAI_Database_Manager::query($query);
        if ($result === false) {
            WP_CLI::error('Query execution failed.');
        } else {
            WP_CLI::success("Query executed successfully. Affected rows: $result");
        }
    });
}