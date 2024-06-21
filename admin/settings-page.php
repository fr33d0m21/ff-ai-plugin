<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

function ffai_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';

    if (isset($_POST['ffai_settings_submit'])) {
        check_admin_referer('ffai_settings_nonce');
        ffai_save_settings();
    }

    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <h2 class="nav-tab-wrapper">
            <a href="?page=ffai-settings&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>"><?php _e('General', 'farming-footprints-ai'); ?></a>
            <a href="?page=ffai-settings&tab=ai" class="nav-tab <?php echo $active_tab == 'ai' ? 'nav-tab-active' : ''; ?>"><?php _e('AI Settings', 'farming-footprints-ai'); ?></a>
            <a href="?page=ffai-settings&tab=weather" class="nav-tab <?php echo $active_tab == 'weather' ? 'nav-tab-active' : ''; ?>"><?php _e('Weather', 'farming-footprints-ai'); ?></a>
            <a href="?page=ffai-settings&tab=iot" class="nav-tab <?php echo $active_tab == 'iot' ? 'nav-tab-active' : ''; ?>"><?php _e('IoT Integration', 'farming-footprints-ai'); ?></a>
            <a href="?page=ffai-settings&tab=advanced" class="nav-tab <?php echo $active_tab == 'advanced' ? 'nav-tab-active' : ''; ?>"><?php _e('Advanced', 'farming-footprints-ai'); ?></a>
        </h2>

        <form method="post" action="">
            <?php
            wp_nonce_field('ffai_settings_nonce');
            
            switch ($active_tab) {
                case 'general':
                    ffai_general_settings();
                    break;
                case 'ai':
                    ffai_ai_settings();
                    break;
                case 'weather':
                    ffai_weather_settings();
                    break;
                case 'iot':
                    ffai_iot_settings();
                    break;
                case 'advanced':
                    ffai_advanced_settings();
                    break;
            }
            ?>
            <p class="submit">
                <input type="submit" name="ffai_settings_submit" class="button-primary" value="<?php _e('Save Settings', 'farming-footprints-ai'); ?>">
            </p>
        </form>
    </div>
    <?php
}

function ffai_general_settings() {
    $default_language = get_option('ffai_default_language', 'en');
    $enable_user_registration = get_option('ffai_enable_user_registration', '0');
    ?>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="ffai_default_language"><?php _e('Default Language', 'farming-footprints-ai'); ?></label></th>
            <td>
                <select id="ffai_default_language" name="ffai_default_language">
                    <option value="en" <?php selected($default_language, 'en'); ?>><?php _e('English', 'farming-footprints-ai'); ?></option>
                    <option value="es" <?php selected($default_language, 'es'); ?>><?php _e('Spanish', 'farming-footprints-ai'); ?></option>
                    <option value="fr" <?php selected($default_language, 'fr'); ?>><?php _e('French', 'farming-footprints-ai'); ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ffai_enable_user_registration"><?php _e('Enable User Registration', 'farming-footprints-ai'); ?></label></th>
            <td>
                <input type="checkbox" id="ffai_enable_user_registration" name="ffai_enable_user_registration" value="1" <?php checked($enable_user_registration, '1'); ?>>
                <p class="description"><?php _e('Allow users to register for personalized experiences.', 'farming-footprints-ai'); ?></p>
            </td>
        </tr>
    </table>
    <?php
}

function ffai_ai_settings() {
    $api_key = get_option('ffai_api_key', '');
    $model_version = get_option('ffai_model_version', 'claude-3-sonnet-20240229');
    ?>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="ffai_api_key"><?php _e('Claude API Key', 'farming-footprints-ai'); ?></label></th>
            <td>
                <input type="password" id="ffai_api_key" name="ffai_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ffai_model_version"><?php _e('AI Model Version', 'farming-footprints-ai'); ?></label></th>
            <td>
                <select id="ffai_model_version" name="ffai_model_version">
                    <option value="claude-3-sonnet-20240229" <?php selected($model_version, 'claude-3-sonnet-20240229'); ?>><?php _e('Claude 3 Sonnet', 'farming-footprints-ai'); ?></option>
                    <option value="claude-3-opus-20240229" <?php selected($model_version, 'claude-3-opus-20240229'); ?>><?php _e('Claude 3 Opus', 'farming-footprints-ai'); ?></option>
                </select>
            </td>
        </tr>
    </table>
    <?php
}

function ffai_weather_settings() {
    $weather_api_key = get_option('ffai_weather_api_key', '');
    $weather_update_frequency = get_option('ffai_weather_update_frequency', 'hourly');
    ?>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="ffai_weather_api_key"><?php _e('Weather API Key', 'farming-footprints-ai'); ?></label></th>
            <td>
                <input type="password" id="ffai_weather_api_key" name="ffai_weather_api_key" value="<?php echo esc_attr($weather_api_key); ?>" class="regular-text">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ffai_weather_update_frequency"><?php _e('Weather Update Frequency', 'farming-footprints-ai'); ?></label></th>
            <td>
                <select id="ffai_weather_update_frequency" name="ffai_weather_update_frequency">
                    <option value="hourly" <?php selected($weather_update_frequency, 'hourly'); ?>><?php _e('Hourly', 'farming-footprints-ai'); ?></option>
                    <option value="daily" <?php selected($weather_update_frequency, 'daily'); ?>><?php _e('Daily', 'farming-footprints-ai'); ?></option>
                </select>
            </td>
        </tr>
    </table>
    <?php
}

function ffai_iot_settings() {
    $enable_iot = get_option('ffai_enable_iot', '0');
    $iot_device_token = get_option('ffai_iot_device_token', '');
    ?>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="ffai_enable_iot"><?php _e('Enable IoT Integration', 'farming-footprints-ai'); ?></label></th>
            <td>
                <input type="checkbox" id="ffai_enable_iot" name="ffai_enable_iot" value="1" <?php checked($enable_iot, '1'); ?>>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ffai_iot_device_token"><?php _e('IoT Device Token', 'farming-footprints-ai'); ?></label></th>
            <td>
                <input type="text" id="ffai_iot_device_token" name="ffai_iot_device_token" value="<?php echo esc_attr($iot_device_token); ?>" class="regular-text">
            </td>
        </tr>
    </table>
    <?php
}

function ffai_advanced_settings() {
    $debug_mode = get_option('ffai_debug_mode', '0');
    $cache_lifetime = get_option('ffai_cache_lifetime', '3600');
    ?>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="ffai_debug_mode"><?php _e('Enable Debug Mode', 'farming-footprints-ai'); ?></label></th>
            <td>
                <input type="checkbox" id="ffai_debug_mode" name="ffai_debug_mode" value="1" <?php checked($debug_mode, '1'); ?>>
                <p class="description"><?php _e('Enable detailed logging for troubleshooting.', 'farming-footprints-ai'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ffai_cache_lifetime"><?php _e('Cache Lifetime (seconds)', 'farming-footprints-ai'); ?></label></th>
            <td>
                <input type="number" id="ffai_cache_lifetime" name="ffai_cache_lifetime" value="<?php echo esc_attr($cache_lifetime); ?>" class="small-text">
                <p class="description"><?php _e('Set how long to cache API responses and computed data.', 'farming-footprints-ai'); ?></p>
            </td>
        </tr>
    </table>
    <?php
}

function ffai_save_settings() {
    $options = array(
        'ffai_default_language',
        'ffai_enable_user_registration',
        'ffai_api_key',
        'ffai_model_version',
        'ffai_weather_api_key',
        'ffai_weather_update_frequency',
        'ffai_enable_iot',
        'ffai_iot_device_token',
        'ffai_debug_mode',
        'ffai_cache_lifetime'
    );

    foreach ($options as $option) {
        if (isset($_POST[$option])) {
            $value = sanitize_text_field($_POST[$option]);
            update_option($option, $value);
        }
    }

    add_settings_error('ffai_settings', 'ffai_settings_updated', __('Settings saved successfully.', 'farming-footprints-ai'), 'updated');
}