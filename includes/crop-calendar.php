<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class FFAI_Crop_Calendar {

    public static function get_crop_calendar($crop, $location = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ffai_crop_calendars';

        $query = $wpdb->prepare("SELECT * FROM $table_name WHERE crop_name = %s", $crop);
        if ($location) {
            $query .= $wpdb->prepare(" AND location = %s", $location);
        }

        $result = $wpdb->get_row($query, ARRAY_A);

        if (!$result) {
            return new WP_Error('ffai_crop_error', __('Crop calendar not found for this crop and location.', 'farming-footprints-ai'));
        }

        return [
            'plant' => $result['planting_start'] . ' to ' . $result['planting_end'],
            'harvest' => $result['harvest_start'] . ' to ' . $result['harvest_end'],
        ];
    }

    public static function get_crop_data($crop) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ffai_crops';

        $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE crop_name = %s", $crop), ARRAY_A);

        if (!$result) {
            return new WP_Error('ffai_crop_error', __('Crop data not found.', 'farming-footprints-ai'));
        }

        return $result;
    }

    public static function add_crop_calendar($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ffai_crop_calendars';

        $result = $wpdb->insert(
            $table_name,
            [
                'crop_name' => $data['crop_name'],
                'location' => $data['location'],
                'planting_start' => $data['planting_start'],
                'planting_end' => $data['planting_end'],
                'harvest_start' => $data['harvest_start'],
                'harvest_end' => $data['harvest_end'],
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s']
        );

        if ($result === false) {
            return new WP_Error('ffai_crop_error', __('Failed to add crop calendar.', 'farming-footprints-ai'));
        }

        return true;
    }

    public static function update_crop_calendar($id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ffai_crop_calendars';

        $result = $wpdb->update(
            $table_name,
            [
                'planting_start' => $data['planting_start'],
                'planting_end' => $data['planting_end'],
                'harvest_start' => $data['harvest_start'],
                'harvest_end' => $data['harvest_end'],
            ],
            ['id' => $id],
            ['%s', '%s', '%s', '%s'],
            ['%d']
        );

        if ($result === false) {
            return new WP_Error('ffai_crop_error', __('Failed to update crop calendar.', 'farming-footprints-ai'));
        }

        return true;
    }

    public static function get_suitable_crops($location, $month) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ffai_crop_calendars';

        $result = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT crop_name FROM $table_name 
                WHERE location = %s 
                AND (
                    (MONTH(planting_start) <= %d AND MONTH(planting_end) >= %d)
                    OR (MONTH(harvest_start) <= %d AND MONTH(harvest_end) >= %d)
                )",
                $location,
                $month,
                $month,
                $month,
                $month
            ),
            ARRAY_A
        );

        if (empty($result)) {
            return new WP_Error('ffai_crop_error', __('No suitable crops found for this location and month.', 'farming-footprints-ai'));
        }

        return array_column($result, 'crop_name');
    }

    public static function get_crop_recommendations($location, $month) {
        $suitable_crops = self::get_suitable_crops($location, $month);

        if (is_wp_error($suitable_crops)) {
            return $suitable_crops;
        }

        $recommendations = [];
        foreach ($suitable_crops as $crop) {
            $crop_data = self::get_crop_data($crop);
            if (!is_wp_error($crop_data)) {
                $recommendations[$crop] = [
                    'name' => $crop,
                    'water_needs' => $crop_data['water_needs'],
                    'soil_type' => $crop_data['preferred_soil'],
                    'growth_period' => $crop_data['growth_period'],
                ];
            }
        }

        return $recommendations;
    }

    public static function update_crop_database() {
        // This function would be used to update the crop database with new information
        // For example, fetching data from an external API or CSV file
        // For now, we'll just log that the function was called
        error_log('FFAI: update_crop_database called at ' . current_time('mysql'));
        return true;
    }
}

// Add custom WP-CLI commands for testing crop calendar functions
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('ffai get-crop-calendar', function($args, $assoc_args) {
        if (count($args) < 1) {
            WP_CLI::error('Please provide a crop name.');
            return;
        }
        $crop = $args[0];
        $location = isset($args[1]) ? $args[1] : null;
        $result = FFAI_Crop_Calendar::get_crop_calendar($crop, $location);
        if (is_wp_error($result)) {
            WP_CLI::error($result->get_error_message());
        } else {
            WP_CLI::success("Crop calendar for $crop: " . json_encode($result));
        }
    });

    WP_CLI::add_command('ffai get-crop-recommendations', function($args, $assoc_args) {
        if (count($args) < 2) {
            WP_CLI::error('Please provide a location and month (1-12).');
            return;
        }
        $location = $args[0];
        $month = intval($args[1]);
        $result = FFAI_Crop_Calendar::get_crop_recommendations($location, $month);
        if (is_wp_error($result)) {
            WP_CLI::error($result->get_error_message());
        } else {
            WP_CLI::success("Crop recommendations for $location in month $month: " . json_encode($result));
        }
    });
}