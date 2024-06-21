<?php
/**
 * Plugin Name: Farming Footprints AI
 * Description: Advanced AI assistant for sustainable farming with Claude 3.5 Sonnet
 * Version: 3.0
 * Author: Daniel Boissonneault
 * Text Domain: farming-footprints-ai
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Define plugin constants
define('FFAI_VERSION', '3.0');
define('FFAI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FFAI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FFAI_INCLUDES_DIR', FFAI_PLUGIN_DIR . 'includes/');
define('FFAI_ADMIN_DIR', FFAI_PLUGIN_DIR . 'admin/');
define('FFAI_PUBLIC_DIR', FFAI_PLUGIN_DIR . 'public/');
define('FFAI_ASSETS_URL', FFAI_PLUGIN_URL . 'assets/');

// Include autoloader for potential third-party libraries
require_once FFAI_PLUGIN_DIR . 'vendor/autoload.php';

// Include necessary files
require_once FFAI_INCLUDES_DIR . 'database-manager.php';
require_once FFAI_INCLUDES_DIR . 'ai-functions.php';
require_once FFAI_INCLUDES_DIR . 'weather-integration.php';
require_once FFAI_INCLUDES_DIR . 'crop-calendar.php';
require_once FFAI_INCLUDES_DIR . 'resource-library.php';
require_once FFAI_INCLUDES_DIR . 'iot-integration.php';
require_once FFAI_INCLUDES_DIR . 'data-visualization.php';
require_once FFAI_INCLUDES_DIR . 'user-authentication.php';
require_once FFAI_INCLUDES_DIR . 'language-processing.php';

require_once FFAI_ADMIN_DIR . 'settings-page.php';
require_once FFAI_ADMIN_DIR . 'user-management.php';
require_once FFAI_ADMIN_DIR . 'dashboard.php';

require_once FFAI_PUBLIC_DIR . 'shortcode-chat.php';
require_once FFAI_PUBLIC_DIR . 'shortcode-crop-planner.php';
require_once FFAI_PUBLIC_DIR . 'shortcode-weather-dashboard.php';

// Activation hook
register_activation_hook(__FILE__, 'ffai_activate_plugin');

function ffai_activate_plugin() {
    FFAI_Database_Manager::create_tables();
    
    // Set default options
    add_option('ffai_api_key', '');
    add_option('ffai_weather_api_key', '');
    add_option('ffai_default_language', 'en');
    add_option('ffai_enable_iot', '0');
    
    // Create necessary pages
    ffai_create_plugin_pages();
    
    // Schedule cron jobs
    wp_schedule_event(time(), 'daily', 'ffai_daily_data_update');
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'ffai_deactivate_plugin');

function ffai_deactivate_plugin() {
    wp_clear_scheduled_hook('ffai_daily_data_update');
}

// Admin menu
add_action('admin_menu', 'ffai_add_admin_menu');

function ffai_add_admin_menu() {
    add_menu_page(
        'Farming Footprints AI',
        'FF AI',
        'manage_options',
        'ffai-dashboard',
        'ffai_dashboard_page',
        FFAI_ASSETS_URL . 'images/icon.png',
        30
    );
    
    add_submenu_page(
        'ffai-dashboard',
        'Settings',
        'Settings',
        'manage_options',
        'ffai-settings',
        'ffai_settings_page'
    );
    
    add_submenu_page(
        'ffai-dashboard',
        'User Management',
        'User Management',
        'manage_options',
        'ffai-user-management',
        'ffai_user_management_page'
    );
}

// Enqueue scripts and styles
add_action('wp_enqueue_scripts', 'ffai_enqueue_scripts');

function ffai_enqueue_scripts() {
    wp_enqueue_style('ffai-main-style', FFAI_PLUGIN_URL . 'public/css/main.css', array(), FFAI_VERSION);
    wp_enqueue_script('ffai-main-script', FFAI_PLUGIN_URL . 'public/js/main.js', array('jquery'), FFAI_VERSION, true);
    
    wp_localize_script('ffai-main-script', 'ffai_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ffai_nonce')
    ));
}

// Register shortcodes
add_shortcode('ffai_chat', 'ffai_chat_shortcode');
add_shortcode('ffai_crop_planner', 'ffai_crop_planner_shortcode');
add_shortcode('ffai_weather_dashboard', 'ffai_weather_dashboard_shortcode');

// Register AJAX actions
add_action('wp_ajax_ffai_chat_request', 'ffai_chat_request');
add_action('wp_ajax_nopriv_ffai_chat_request', 'ffai_chat_request');
add_action('wp_ajax_ffai_image_analysis', 'ffai_image_analysis');
add_action('wp_ajax_nopriv_ffai_image_analysis', 'ffai_image_analysis');
add_action('wp_ajax_ffai_get_crop_plan', 'ffai_get_crop_plan');
add_action('wp_ajax_ffai_save_crop_plan', 'ffai_save_crop_plan');
add_action('wp_ajax_ffai_get_weather_data', 'ffai_get_weather_data');

// Load text domain for translations
add_action('plugins_loaded', 'ffai_load_textdomain');

function ffai_load_textdomain() {
    load_plugin_textdomain('farming-footprints-ai', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}

// Create necessary pages for the plugin
function ffai_create_plugin_pages() {
    $pages = array(
        'ai-assistant' => 'AI Farming Assistant',
        'crop-planner' => 'Crop Planner',
        'weather-dashboard' => 'Weather Dashboard'
    );
    
    foreach ($pages as $slug => $title) {
        if (get_page_by_path($slug) === null) {
            wp_insert_post(array(
                'post_title' => $title,
                'post_content' => '[ffai_' . str_replace('-', '_', $slug) . ']',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_name' => $slug
            ));
        }
    }
}

// Daily cron job for data updates
add_action('ffai_daily_data_update', 'ffai_perform_daily_updates');

function ffai_perform_daily_updates() {
    // Update crop database
    FFAI_Crop_Calendar::update_crop_database();
    
    // Update weather forecasts
    FFAI_Weather_Integration::update_forecasts();
    
    // Perform AI model fine-tuning if needed
    FFAI_AI_Functions::perform_model_update();
}