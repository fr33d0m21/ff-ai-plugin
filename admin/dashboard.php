<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

function ffai_dashboard_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'farming-footprints-ai'));
    }

    ?>
    <div class="wrap">
        <h1><?php _e('Farming Footprints AI Dashboard', 'farming-footprints-ai'); ?></h1>

        <div id="dashboard-widgets-wrap">
            <div id="dashboard-widgets" class="metabox-holder">
                <div id="postbox-container-1" class="postbox-container">
                    <?php ffai_dashboard_summary_widget(); ?>
                    <?php ffai_dashboard_recent_activities_widget(); ?>
                </div>
                <div id="postbox-container-2" class="postbox-container">
                    <?php ffai_dashboard_weather_widget(); ?>
                    <?php ffai_dashboard_quick_actions_widget(); ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function ffai_dashboard_summary_widget() {
    $total_users = count_users();
    $farmer_count = $total_users['avail_roles']['ffai_farmer'] ?? 0;
    $total_interactions = ffai_get_total_ai_interactions();
    $total_crops = ffai_get_total_crops_monitored();

    ?>
    <div class="postbox">
        <h2 class="hndle"><span><?php _e('Summary', 'farming-footprints-ai'); ?></span></h2>
        <div class="inside">
            <ul>
                <li><?php printf(__('Total Farmers: %d', 'farming-footprints-ai'), $farmer_count); ?></li>
                <li><?php printf(__('Total AI Interactions: %d', 'farming-footprints-ai'), $total_interactions); ?></li>
                <li><?php printf(__('Crops Monitored: %d', 'farming-footprints-ai'), $total_crops); ?></li>
            </ul>
        </div>
    </div>
    <?php
}

function ffai_dashboard_recent_activities_widget() {
    $recent_activities = ffai_get_recent_activities();

    ?>
    <div class="postbox">
        <h2 class="hndle"><span><?php _e('Recent Activities', 'farming-footprints-ai'); ?></span></h2>
        <div class="inside">
            <?php if ($recent_activities) : ?>
                <ul>
                    <?php foreach ($recent_activities as $activity) : ?>
                        <li><?php echo esc_html($activity); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p><?php _e('No recent activities.', 'farming-footprints-ai'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function ffai_dashboard_weather_widget() {
    $weather_data = ffai_get_current_weather();

    ?>
    <div class="postbox">
        <h2 class="hndle"><span><?php _e('Current Weather', 'farming-footprints-ai'); ?></span></h2>
        <div class="inside">
            <?php if ($weather_data) : ?>
                <p><?php printf(__('Temperature: %s°C', 'farming-footprints-ai'), esc_html($weather_data['temperature'])); ?></p>
                <p><?php printf(__('Condition: %s', 'farming-footprints-ai'), esc_html($weather_data['condition'])); ?></p>
                <p><?php printf(__('Humidity: %s%%', 'farming-footprints-ai'), esc_html($weather_data['humidity'])); ?></p>
            <?php else : ?>
                <p><?php _e('Weather data unavailable.', 'farming-footprints-ai'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function ffai_dashboard_quick_actions_widget() {
    ?>
    <div class="postbox">
        <h2 class="hndle"><span><?php _e('Quick Actions', 'farming-footprints-ai'); ?></span></h2>
        <div class="inside">
            <a href="<?php echo admin_url('admin.php?page=ffai-user-management&action=add'); ?>" class="button button-primary"><?php _e('Add New Farmer', 'farming-footprints-ai'); ?></a>
            <a href="<?php echo admin_url('admin.php?page=ffai-settings'); ?>" class="button button-secondary"><?php _e('Configure Settings', 'farming-footprints-ai'); ?></a>
            <a href="#" class="button button-secondary" onclick="ffai_run_data_sync(); return false;"><?php _e('Sync Data', 'farming-footprints-ai'); ?></a>
        </div>
    </div>
    <?php
}

function ffai_get_total_ai_interactions() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ffai_interactions';
    return $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
}

function ffai_get_total_crops_monitored() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ffai_crops';
    return $wpdb->get_var("SELECT COUNT(DISTINCT crop_name) FROM $table_name");
}

function ffai_get_recent_activities() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ffai_activities';
    $activities = $wpdb->get_results("SELECT activity_description, activity_date FROM $table_name ORDER BY activity_date DESC LIMIT 5", ARRAY_A);

    $formatted_activities = array();
    foreach ($activities as $activity) {
        $formatted_activities[] = sprintf(
            '%s: %s',
            date_i18n(get_option('date_format'), strtotime($activity['activity_date'])),
            $activity['activity_description']
        );
    }

    return $formatted_activities;
}

function ffai_get_current_weather() {
    // This is a placeholder. In a real-world scenario, you would integrate with a weather API.
    // For demonstration purposes, we'll return dummy data.
    return array(
        'temperature' => '22',
        'condition' => 'Partly Cloudy',
        'humidity' => '65',
    );
}

// AJAX handler for data sync
add_action('wp_ajax_ffai_run_data_sync', 'ffai_ajax_run_data_sync');

function ffai_ajax_run_data_sync() {
    // Perform data synchronization tasks here
    // This could include updating weather data, syncing with IoT devices, etc.

    // For demonstration, we'll just return a success message
    wp_send_json_success(__('Data synchronization completed successfully.', 'farming-footprints-ai'));
}

// Enqueue dashboard scripts
add_action('admin_enqueue_scripts', 'ffai_enqueue_dashboard_scripts');

function ffai_enqueue_dashboard_scripts($hook) {
    if ('toplevel_page_ffai-dashboard' !== $hook) {
        return;
    }

    wp_enqueue_script('ffai-dashboard-script', FFAI_PLUGIN_URL . 'admin/js/dashboard.js', array('jquery'), FFAI_VERSION, true);
    wp_localize_script('ffai-dashboard-script', 'ffai_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ffai_dashboard_nonce')
    ));
}