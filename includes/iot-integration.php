<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class FFAI_IoT_Integration {

    private static $api_base_url = 'https://api.farmingfootprints.com/iot/'; // Replace with actual API endpoint

    public static function register_device($device_data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ffai_iot_devices';

        $result = $wpdb->insert(
            $table_name,
            [
                'device_id' => $device_data['device_id'],
                'device_type' => $device_data['device_type'],
                'user_id' => get_current_user_id(),
                'location' => $device_data['location'],
                'last_sync' => current_time('mysql'),
            ],
            ['%s', '%s', '%d', '%s', '%s']
        );

        if ($result === false) {
            return new WP_Error('ffai_iot_error', __('Failed to register IoT device.', 'farming-footprints-ai'));
        }

        return $wpdb->insert_id;
    }

    public static function get_device_data($device_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ffai_iot_devices';

        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE device_id = %s",
            $device_id
        ), ARRAY_A);

        if (!$result) {
            return new WP_Error('ffai_iot_error', __('Device not found.', 'farming-footprints-ai'));
        }

        return $result;
    }

    public static function update_device_data($device_id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ffai_iot_devices';

        $result = $wpdb->update(
            $table_name,
            $data,
            ['device_id' => $device_id],
            ['%s', '%s', '%s'],
            ['%s']
        );

        if ($result === false) {
            return new WP_Error('ffai_iot_error', __('Failed to update device data.', 'farming-footprints-ai'));
        }

        return true;
    }

    public static function fetch_sensor_data($device_id) {
        $api_key = get_option('ffai_iot_api_key', '');
        if (empty($api_key)) {
            return new WP_Error('ffai_iot_error', __('IoT API key is not configured.', 'farming-footprints-ai'));
        }

        $url = self::$api_base_url . "sensor-data/{$device_id}";
        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
            ],
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('ffai_iot_error', __('Failed to fetch sensor data.', 'farming-footprints-ai'));
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!$data || !isset($data['sensor_data'])) {
            return new WP_Error('ffai_iot_error', __('Invalid sensor data received.', 'farming-footprints-ai'));
        }

        self::store_sensor_data($device_id, $data['sensor_data']);

        return $data['sensor_data'];
    }

    private static function store_sensor_data($device_id, $sensor_data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ffai_iot_sensor_data';

        foreach ($sensor_data as $sensor_type => $value) {
            $wpdb->insert(
                $table_name,
                [
                    'device_id' => $device_id,
                    'sensor_type' => $sensor_type,
                    'value' => $value,
                    'timestamp' => current_time('mysql'),
                ],
                ['%s', '%s', '%f', '%s']
            );
        }
    }

    public static function get_latest_sensor_data($device_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ffai_iot_sensor_data';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT sensor_type, value, timestamp 
            FROM $table_name 
            WHERE device_id = %s 
            AND timestamp = (
                SELECT MAX(timestamp) 
                FROM $table_name 
                WHERE device_id = %s 
                GROUP BY sensor_type
            )",
            $device_id,
            $device_id
        ), ARRAY_A);

        if (empty($results)) {
            return new WP_Error('ffai_iot_error', __('No sensor data found for this device.', 'farming-footprints-ai'));
        }

        $sensor_data = [];
        foreach ($results as $row) {
            $sensor_data[$row['sensor_type']] = [
                'value' => $row['value'],
                'timestamp' => $row['timestamp'],
            ];
        }

        return $sensor_data;
    }

    public static function analyze_sensor_data($device_id) {
        $sensor_data = self::get_latest_sensor_data($device_id);
        if (is_wp_error($sensor_data)) {
            return $sensor_data;
        }

        $device_data = self::get_device_data($device_id);
        if (is_wp_error($device_data)) {
            return $device_data;
        }

        $analysis = [];

        // Soil moisture analysis
        if (isset($sensor_data['soil_moisture'])) {
            $moisture = $sensor_data['soil_moisture']['value'];
            if ($moisture < 30) {
                $analysis['soil_moisture'] = __('Soil moisture is low. Consider irrigating soon.', 'farming-footprints-ai');
            } elseif ($moisture > 70) {
                $analysis['soil_moisture'] = __('Soil moisture is high. Be cautious of overwatering.', 'farming-footprints-ai');
            } else {
                $analysis['soil_moisture'] = __('Soil moisture is at an optimal level.', 'farming-footprints-ai');
            }
        }

        // Temperature analysis
        if (isset($sensor_data['temperature'])) {
            $temp = $sensor_data['temperature']['value'];
            if ($temp < 10) {
                $analysis['temperature'] = __('Temperature is low. Be aware of potential frost risk.', 'farming-footprints-ai');
            } elseif ($temp > 35) {
                $analysis['temperature'] = __('Temperature is high. Consider providing shade or additional irrigation.', 'farming-footprints-ai');
            } else {
                $analysis['temperature'] = __('Temperature is within a normal range for most crops.', 'farming-footprints-ai');
            }
        }

        // Add more sensor data analysis as needed

        return $analysis;
    }

    public static function trigger_action($device_id, $action) {
        $api_key = get_option('ffai_iot_api_key', '');
        if (empty($api_key)) {
            return new WP_Error('ffai_iot_error', __('IoT API key is not configured.', 'farming-footprints-ai'));
        }

        $url = self::$api_base_url . "trigger-action/{$device_id}";
        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode(['action' => $action]),
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('ffai_iot_error', __('Failed to trigger action.', 'farming-footprints-ai'));
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!$data || !isset($data['status'])) {
            return new WP_Error('ffai_iot_error', __('Invalid response received.', 'farming-footprints-ai'));
        }

        return $data['status'] === 'success';
    }
}

// Add custom WP-CLI commands for testing IoT integration functions
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('ffai get-iot-data', function($args, $assoc_args) {
        if (count($args) < 1) {
            WP_CLI::error('Please provide a device ID.');
            return;
        }
        $device_id = $args[0];
        $result = FFAI_IoT_Integration::get_latest_sensor_data($device_id);
        if (is_wp_error($result)) {
            WP_CLI::error($result->get_error_message());
        } else {
            WP_CLI::success("Latest sensor data: " . json_encode($result));
        }
    });

    WP_CLI::add_command('ffai analyze-iot-data', function($args, $assoc_args) {
        if (count($args) < 1) {
            WP_CLI::error('Please provide a device ID.');
            return;
        }
        $device_id = $args[0];
        $result = FFAI_IoT_Integration::analyze_sensor_data($device_id);
        if (is_wp_error($result)) {
            WP_CLI::error($result->get_error_message());
        } else {
            WP_CLI::success("Sensor data analysis: " . json_encode($result));
        }
    });
}