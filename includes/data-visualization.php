<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class FFAI_Data_Visualization {

    public static function generate_chart($type, $data, $options = []) {
        // Enqueue necessary scripts
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '3.7.0', true);
        wp_enqueue_script('ffai-charts', FFAI_PLUGIN_URL . 'public/js/ffai-charts.js', ['chart-js'], FFAI_VERSION, true);

        $chart_id = 'ffai-chart-' . uniqid();
        $chart_data = json_encode($data);
        $chart_options = json_encode($options);

        ob_start();
        ?>
        <div class="ffai-chart-container">
            <canvas id="<?php echo esc_attr($chart_id); ?>"></canvas>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                FFAI_Charts.createChart('<?php echo esc_js($chart_id); ?>', '<?php echo esc_js($type); ?>', <?php echo $chart_data; ?>, <?php echo $chart_options; ?>);
            });
        </script>
        <?php
        return ob_get_clean();
    }

    public static function crop_yield_chart($user_id, $crop, $years = 5) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ffai_crop_yields';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT year, yield FROM $table_name WHERE user_id = %d AND crop = %s ORDER BY year DESC LIMIT %d",
            $user_id,
            $crop,
            $years
        ), ARRAY_A);

        if (empty($results)) {
            return new WP_Error('ffai_viz_error', __('No yield data available for this crop.', 'farming-footprints-ai'));
        }

        $data = [
            'labels' => array_column(array_reverse($results), 'year'),
            'datasets' => [
                [
                    'label' => sprintf(__('%s Yield', 'farming-footprints-ai'), ucfirst($crop)),
                    'data' => array_column(array_reverse($results), 'yield'),
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 1
                ]
            ]
        ];

        $options = [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => __('Yield (tons/acre)', 'farming-footprints-ai')
                    ]
                ]
            ]
        ];

        return self::generate_chart('bar', $data, $options);
    }

    public static function soil_moisture_chart($device_id, $days = 7) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ffai_iot_sensor_data';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(timestamp) as date, AVG(value) as avg_moisture 
            FROM $table_name 
            WHERE device_id = %s AND sensor_type = 'soil_moisture'
            AND timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY DATE(timestamp)
            ORDER BY DATE(timestamp) ASC",
            $device_id,
            $days
        ), ARRAY_A);

        if (empty($results)) {
            return new WP_Error('ffai_viz_error', __('No soil moisture data available for this period.', 'farming-footprints-ai'));
        }

        $data = [
            'labels' => array_column($results, 'date'),
            'datasets' => [
                [
                    'label' => __('Average Soil Moisture', 'farming-footprints-ai'),
                    'data' => array_column($results, 'avg_moisture'),
                    'fill' => false,
                    'borderColor' => 'rgb(75, 192, 192)',
                    'tension' => 0.1
                ]
            ]
        ];

        $options = [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => __('Soil Moisture (%)', 'farming-footprints-ai')
                    ]
                ]
            ]
        ];

        return self::generate_chart('line', $data, $options);
    }

    public static function weather_comparison_chart($location, $days = 30) {
        $weather_data = FFAI_Weather_Integration::get_historical_weather($location, $days);
        if (is_wp_error($weather_data)) {
            return $weather_data;
        }

        $data = [
            'labels' => array_keys($weather_data),
            'datasets' => [
                [
                    'label' => __('Temperature (°C)', 'farming-footprints-ai'),
                    'data' => array_column($weather_data, 'temperature'),
                    'borderColor' => 'rgb(255, 99, 132)',
                    'yAxisID' => 'y-temperature',
                ],
                [
                    'label' => __('Rainfall (mm)', 'farming-footprints-ai'),
                    'data' => array_column($weather_data, 'rainfall'),
                    'borderColor' => 'rgb(54, 162, 235)',
                    'yAxisID' => 'y-rainfall',
                ]
            ]
        ];

        $options = [
            'scales' => [
                'y-temperature' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => __('Temperature (°C)', 'farming-footprints-ai')
                    ]
                ],
                'y-rainfall' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => __('Rainfall (mm)', 'farming-footprints-ai')
                    ],
                    'grid' => [
                        'drawOnChartArea' => false
                    ]
                ]
            ]
        ];

        return self::generate_chart('line', $data, $options);
    }

    public static function crop_comparison_radar($user_id, $year) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ffai_crop_yields';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT crop, yield FROM $table_name WHERE user_id = %d AND year = %d",
            $user_id,
            $year
        ), ARRAY_A);

        if (empty($results)) {
            return new WP_Error('ffai_viz_error', __('No yield data available for this year.', 'farming-footprints-ai'));
        }

        $data = [
            'labels' => array_column($results, 'crop'),
            'datasets' => [
                [
                    'label' => sprintf(__('Crop Yields %d', 'farming-footprints-ai'), $year),
                    'data' => array_column($results, 'yield'),
                    'fill' => true,
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'pointBackgroundColor' => 'rgb(255, 99, 132)',
                    'pointBorderColor' => '#fff',
                    'pointHoverBackgroundColor' => '#fff',
                    'pointHoverBorderColor' => 'rgb(255, 99, 132)'
                ]
            ]
        ];

        $options = [
            'elements' => [
                'line' => [
                    'borderWidth' => 3
                ]
            ]
        ];

        return self::generate_chart('radar', $data, $options);
    }
}

// Add custom WP-CLI commands for testing data visualization functions
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('ffai generate-chart', function($args, $assoc_args) {
        if (count($args) < 2) {
            WP_CLI::error('Please provide a chart type and user ID.');
            return;
        }
        $chart_type = $args[0];
        $user_id = intval($args[1]);

        switch ($chart_type) {
            case 'crop_yield':
                $crop = isset($args[2]) ? $args[2] : 'corn';
                $result = FFAI_Data_Visualization::crop_yield_chart($user_id, $crop);
                break;
            case 'soil_moisture':
                $device_id = isset($args[2]) ? $args[2] : 'default_device';
                $result = FFAI_Data_Visualization::soil_moisture_chart($device_id);
                break;
            case 'weather_comparison':
                $location = isset($args[2]) ? $args[2] : 'New York';
                $result = FFAI_Data_Visualization::weather_comparison_chart($location);
                break;
            case 'crop_comparison':
                $year = isset($args[2]) ? intval($args[2]) : date('Y');
                $result = FFAI_Data_Visualization::crop_comparison_radar($user_id, $year);
                break;
            default:
                WP_CLI::error('Invalid chart type.');
                return;
        }

        if (is_wp_error($result)) {
            WP_CLI::error($result->get_error_message());
        } else {
            WP_CLI::success("Chart HTML generated successfully.");
            WP_CLI::log($result);
        }
    });
}