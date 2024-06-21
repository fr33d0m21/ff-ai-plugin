<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class FFAI_Weather_Integration {

    private static $api_base_url = 'https://api.openweathermap.org/data/2.5/';
    private static $geocoding_url = 'https://api.openweathermap.org/geo/1.0/direct';

    public static function get_weather_data($location) {
        $api_key = get_option('ffai_weather_api_key', '');
        if (empty($api_key)) {
            return new WP_Error('ffai_weather_error', __('Weather API key is not configured.', 'farming-footprints-ai'));
        }

        $coordinates = self::get_coordinates($location);
        if (is_wp_error($coordinates)) {
            return $coordinates;
        }

        $url = self::$api_base_url . "weather?lat={$coordinates['lat']}&lon={$coordinates['lon']}&appid={$api_key}&units=metric";

        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return new WP_Error('ffai_weather_error', __('Failed to fetch weather data.', 'farming-footprints-ai'));
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!$data || !isset($data['main']) || !isset($data['weather'][0])) {
            return new WP_Error('ffai_weather_error', __('Invalid weather data received.', 'farming-footprints-ai'));
        }

        return [
            'temperature' => round($data['main']['temp']),
            'humidity' => $data['main']['humidity'],
            'description' => $data['weather'][0]['description'],
            'icon' => $data['weather'][0]['icon'],
        ];
    }

    public static function get_weather_forecast($location, $days = 5) {
        $api_key = get_option('ffai_weather_api_key', '');
        if (empty($api_key)) {
            return new WP_Error('ffai_weather_error', __('Weather API key is not configured.', 'farming-footprints-ai'));
        }

        $coordinates = self::get_coordinates($location);
        if (is_wp_error($coordinates)) {
            return $coordinates;
        }

        $url = self::$api_base_url . "forecast?lat={$coordinates['lat']}&lon={$coordinates['lon']}&appid={$api_key}&units=metric&cnt=" . ($days * 8);

        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return new WP_Error('ffai_weather_error', __('Failed to fetch weather forecast.', 'farming-footprints-ai'));
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!$data || !isset($data['list'])) {
            return new WP_Error('ffai_weather_error', __('Invalid forecast data received.', 'farming-footprints-ai'));
        }

        $forecast = [];
        $current_date = '';

        foreach ($data['list'] as $item) {
            $date = date('Y-m-d', $item['dt']);
            if ($date !== $current_date) {
                $current_date = $date;
                $forecast[$date] = [
                    'temperature' => round($item['main']['temp']),
                    'humidity' => $item['main']['humidity'],
                    'description' => $item['weather'][0]['description'],
                    'icon' => $item['weather'][0]['icon'],
                ];
            }
        }

        return array_slice($forecast, 0, $days);
    }

    private static function get_coordinates($location) {
        $api_key = get_option('ffai_weather_api_key', '');
        $url = self::$geocoding_url . "?q=" . urlencode($location) . "&limit=1&appid={$api_key}";

        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return new WP_Error('ffai_geocoding_error', __('Failed to geocode location.', 'farming-footprints-ai'));
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data)) {
            return new WP_Error('ffai_geocoding_error', __('Location not found.', 'farming-footprints-ai'));
        }

        return [
            'lat' => $data[0]['lat'],
            'lon' => $data[0]['lon'],
        ];
    }

    public static function update_forecasts() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ffai_locations';

        $locations = $wpdb->get_results("SELECT DISTINCT location FROM $table_name", ARRAY_A);

        foreach ($locations as $location) {
            $forecast = self::get_weather_forecast($location['location']);
            if (!is_wp_error($forecast)) {
                self::save_forecast($location['location'], $forecast);
            }
        }
    }

    private static function save_forecast($location, $forecast) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ffai_weather_forecasts';

        $wpdb->delete($table_name, ['location' => $location], ['%s']);

        foreach ($forecast as $date => $data) {
            $wpdb->insert(
                $table_name,
                [
                    'location' => $location,
                    'forecast_date' => $date,
                    'temperature' => $data['temperature'],
                    'humidity' => $data['humidity'],
                    'description' => $data['description'],
                    'icon' => $data['icon'],
                ],
                ['%s', '%s', '%d', '%d', '%s', '%s']
            );
        }
    }

    public static function get_saved_forecast($location) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ffai_weather_forecasts';

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE location = %s ORDER BY forecast_date ASC",
                $location
            ),
            ARRAY_A
        );

        if (empty($results)) {
            return new WP_Error('ffai_weather_error', __('No saved forecast found for this location.', 'farming-footprints-ai'));
        }

        $forecast = [];
        foreach ($results as $row) {
            $forecast[$row['forecast_date']] = [
                'temperature' => $row['temperature'],
                'humidity' => $row['humidity'],
                'description' => $row['description'],
                'icon' => $row['icon'],
            ];
        }

        return $forecast;
    }
}

// Add a custom WP-CLI command for testing weather integration
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('ffai test-weather', function($args, $assoc_args) {
        $location = isset($args[0]) ? $args[0] : 'New York';
        $result = FFAI_Weather_Integration::get_weather_data($location);
        if (is_wp_error($result)) {
            WP_CLI::error($result->get_error_message());
        } else {
            WP_CLI::success("Current weather in $location: " . json_encode($result));
        }
    });
}