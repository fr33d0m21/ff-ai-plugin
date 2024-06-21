<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class FFAI_Shortcode_Crop_Planner {

    public static function init() {
        add_shortcode('ffai_crop_planner', array(__CLASS__, 'crop_planner_shortcode'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
        add_action('wp_ajax_ffai_get_crop_plan', array(__CLASS__, 'get_crop_plan'));
        add_action('wp_ajax_nopriv_ffai_get_crop_plan', array(__CLASS__, 'get_crop_plan'));
        add_action('wp_ajax_ffai_save_crop_plan', array(__CLASS__, 'save_crop_plan'));
    }

    public static function crop_planner_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => __('Crop Planner', 'farming-footprints-ai'),
        ), $atts, 'ffai_crop_planner');

        ob_start();
        ?>
        <div id="ffai-crop-planner" class="ffai-crop-planner">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <form id="ffai-crop-planner-form">
                <label for="ffai-location"><?php _e('Location:', 'farming-footprints-ai'); ?></label>
                <input type="text" id="ffai-location" name="location" required>

                <label for="ffai-farm-size"><?php _e('Farm Size (acres):', 'farming-footprints-ai'); ?></label>
                <input type="number" id="ffai-farm-size" name="farm_size" min="1" required>

                <label for="ffai-farming-method"><?php _e('Farming Method:', 'farming-footprints-ai'); ?></label>
                <select id="ffai-farming-method" name="farming_method" required>
                    <option value="conventional"><?php _e('Conventional', 'farming-footprints-ai'); ?></option>
                    <option value="organic"><?php _e('Organic', 'farming-footprints-ai'); ?></option>
                    <option value="no-till"><?php _e('No-Till', 'farming-footprints-ai'); ?></option>
                </select>

                <label for="ffai-previous-crops"><?php _e('Previous Crops:', 'farming-footprints-ai'); ?></label>
                <input type="text" id="ffai-previous-crops" name="previous_crops" placeholder="<?php _e('e.g., Corn, Soybeans', 'farming-footprints-ai'); ?>">

                <button type="submit"><?php _e('Generate Crop Plan', 'farming-footprints-ai'); ?></button>
            </form>

            <div id="ffai-crop-plan-result" class="ffai-crop-plan-result" style="display: none;">
                <h4><?php _e('Your Crop Plan', 'farming-footprints-ai'); ?></h4>
                <div id="ffai-crop-plan-content"></div>
                <button id="ffai-save-plan" style="display: none;"><?php _e('Save Plan', 'farming-footprints-ai'); ?></button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function enqueue_scripts() {
        wp_enqueue_style('ffai-crop-planner-style', FFAI_PLUGIN_URL . 'public/css/crop-planner.css', array(), FFAI_VERSION);
        wp_enqueue_script('ffai-crop-planner-script', FFAI_PLUGIN_URL . 'public/js/crop-planner.js', array('jquery'), FFAI_VERSION, true);
        wp_localize_script('ffai-crop-planner-script', 'ffai_crop_planner_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ffai_crop_planner_nonce'),
        ));
    }

    public static function get_crop_plan() {
        check_ajax_referer('ffai_crop_planner_nonce', 'nonce');

        $location = sanitize_text_field($_POST['location']);
        $farm_size = intval($_POST['farm_size']);
        $farming_method = sanitize_text_field($_POST['farming_method']);
        $previous_crops = sanitize_text_field($_POST['previous_crops']);

        // Get weather data for the location
        $weather_data = FFAI_Weather_Integration::get_weather_forecast($location);

        // Get soil data (assuming you have a method for this)
        $soil_data = self::get_soil_data($location);

        // Generate crop recommendations
        $recommendations = self::generate_crop_recommendations($farm_size, $farming_method, $previous_crops, $weather_data, $soil_data);

        // Calculate planting dates
        $planting_dates = self::calculate_planting_dates($recommendations, $weather_data);

        $crop_plan = array(
            'recommendations' => $recommendations,
            'planting_dates' => $planting_dates,
            'additional_info' => self::get_additional_info($farming_method),
        );

        wp_send_json_success($crop_plan);
    }

    public static function save_crop_plan() {
        check_ajax_referer('ffai_crop_planner_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to save a crop plan.', 'farming-footprints-ai'));
        }

        $user_id = get_current_user_id();
        $plan_data = json_decode(stripslashes($_POST['plan_data']), true);

        $result = FFAI_Database_Manager::insert('crop_plans', array(
            'user_id' => $user_id,
            'plan_data' => json_encode($plan_data),
            'created_at' => current_time('mysql'),
        ));

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(__('Crop plan saved successfully.', 'farming-footprints-ai'));
        }
    }

    private static function get_soil_data($location) {
        // This is a placeholder. In a real-world scenario, you would integrate with a soil database or API
        return array(
            'type' => 'loam',
            'ph' => 6.5,
            'organic_matter' => '3%',
        );
    }

    private static function generate_crop_recommendations($farm_size, $farming_method, $previous_crops, $weather_data, $soil_data) {
        // This is a simplified recommendation system. In a real-world scenario, this would be much more complex
        $recommendations = array();

        $crop_options = self::get_crop_options($farming_method, $soil_data);
        $previous_crops_array = array_map('trim', explode(',', $previous_crops));

        foreach ($crop_options as $crop) {
            if (!in_array($crop, $previous_crops_array)) {
                $recommendations[] = $crop;
            }
        }

        // Limit recommendations to 3 crops
        $recommendations = array_slice($recommendations, 0, 3);

        return $recommendations;
    }

    private static function get_crop_options($farming_method, $soil_data) {
        // This is a simplified list. In a real-world scenario, this would be based on much more detailed data
        $options = array(
            'conventional' => array('Corn', 'Soybeans', 'Wheat', 'Barley', 'Canola'),
            'organic' => array('Quinoa', 'Lentils', 'Oats', 'Buckwheat', 'Millet'),
            'no-till' => array('Soybeans', 'Wheat', 'Sorghum', 'Sunflowers', 'Rye'),
        );

        return $options[$farming_method] ?? $options['conventional'];
    }

    private static function calculate_planting_dates($recommendations, $weather_data) {
        // This is a simplified calculation. In a real-world scenario, this would be much more complex
        $planting_dates = array();

        foreach ($recommendations as $crop) {
            // Assume planting date is 2 weeks from now for this example
            $planting_dates[$crop] = date('Y-m-d', strtotime('+2 weeks'));
        }

        return $planting_dates;
    }

    private static function get_additional_info($farming_method) {
        $info = array(
            'conventional' => __('Remember to consider crop rotation and soil health in your conventional farming practices.', 'farming-footprints-ai'),
            'organic' => __('Focus on natural pest control methods and organic fertilizers for your organic crops.', 'farming-footprints-ai'),
            'no-till' => __('Maintain soil cover and minimize soil disturbance to maximize the benefits of no-till farming.', 'farming-footprints-ai'),
        );

        return $info[$farming_method] ?? '';
    }
}

FFAI_Shortcode_Crop_Planner::init();