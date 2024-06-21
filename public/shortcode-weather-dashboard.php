shortcode-weather-dashboard.php<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class FFAI_Shortcode_Weather_Dashboard {

    public static function init() {
        add_shortcode('ffai_weather_dashboard', array(__CLASS__, 'weather_dashboard_shortcode'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
        add_action('wp_ajax_ffai_get_weather_data', array(__CLASS__, 'get_weather_data'));
        add_action('wp_ajax_nopriv_ffai_get_weather_data', array(__CLASS__, 'get_weather_data'));
    }

    public static function weather_dashboard_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => __('Weather Dashboard', 'farming-footprints-ai'),
            'location' => '',
        ), $atts, 'ffai_weather_dashboard');

        ob_start();
        ?>
        <div id="ffai-weather-dashboard" class="ffai-weather-dashboard" data-location="<?php echo esc_attr($atts['location']); ?>">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <div class="ffai-weather-search">
                <input type="text" id="ffai-weather-location" placeholder="<?php _e('Enter location', 'farming-footprints-ai'); ?>" value="<?php echo esc_attr($atts['location']); ?>">
                <button id="ffai-weather-search"><?php _e('Search', 'farming-footprints-ai'); ?></button>
            </div>
            <div id="ffai-weather-content" class="ffai-weather-content">
                <div id="ffai-weather-current"></div>
                <div id="ffai-weather-forecast"></div>
                <div id="ffai-weather-agriculture"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function enqueue_scripts() {
        wp_enqueue_style('ffai-weather-dashboard-style', FFAI_PLUGIN_URL . 'public/css/weather-dashboard.css', array(), FFAI_VERSION);
        wp_enqueue_script('ffai-weather-dashboard-script', FFAI_PLUGIN_URL . 'public/js/weather-dashboard.js', array('jquery', 'chart-js'), FFAI_VERSION, true);
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.0', true);
        wp_localize_script('ffai-weather-dashboard-script', 'ffai_weather_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ffai_weather_nonce'),
        ));
    }

    public static function get_weather_data() {
        check_ajax_referer('ffai_weather_nonce', 'nonce');

        $location = sanitize_text_field($_POST['location']);

        // Get current weather
        $current_weather = FFAI_Weather_Integration::get_current_weather($location);

        // Get weather forecast
        $forecast = FFAI_Weather_Integration::get_weather_forecast($location, 7); // 7-day forecast

        // Get agricultural weather insights
        $agri_insights = self::get_agricultural_insights($location, $current_weather, $forecast);

        $weather_data = array(
            'current' => $current_weather,
            'forecast' => $forecast,
            'agri_insights' => $agri_insights,
        );

        wp_send_json_success($weather_data);
    }

    private static function get_agricultural_insights($location, $current_weather, $forecast) {
        // This is a placeholder. In a real-world scenario, you would implement more sophisticated logic
        $insights = array();

        // Check for extreme temperatures
        if ($current_weather['temperature'] > 35) {
            $insights[] = __('High temperature alert: Consider additional irrigation for heat-sensitive crops.', 'farming-footprints-ai');
        } elseif ($current_weather['temperature'] < 5) {
            $insights[] = __('Low temperature alert: Protect frost-sensitive crops.', 'farming-footprints-ai');
        }

        // Check for precipitation
        $total_precipitation = array_sum(array_column($forecast, 'precipitation'));
        if ($total_precipitation < 10) {
            $insights[] = __('Low precipitation forecast: Plan for irrigation in the coming week.', 'farming-footprints-ai');
        } elseif ($total_precipitation > 50) {
            $insights[] = __('High precipitation forecast: Be prepared for potential flooding or waterlogging.', 'farming-footprints-ai');
        }

        // Add more agricultural insights based on the weather data

        return $insights;
    }
}

FFAI_Shortcode_Weather_Dashboard::init();