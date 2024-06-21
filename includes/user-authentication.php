<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class FFAI_User_Authentication {

    public static function init() {
        add_action('wp_login', array(__CLASS__, 'log_user_login'), 10, 2);
        add_action('user_register', array(__CLASS__, 'set_default_user_role'));
        add_action('show_user_profile', array(__CLASS__, 'add_farmer_profile_fields'));
        add_action('edit_user_profile', array(__CLASS__, 'add_farmer_profile_fields'));
        add_action('personal_options_update', array(__CLASS__, 'save_farmer_profile_fields'));
        add_action('edit_user_profile_update', array(__CLASS__, 'save_farmer_profile_fields'));
    }

    public static function log_user_login($user_login, $user) {
        update_user_meta($user->ID, 'last_login', current_time('mysql'));
    }

    public static function set_default_user_role($user_id) {
        $user = new WP_User($user_id);
        $user->set_role('ffai_farmer');
    }

    public static function add_farmer_profile_fields($user) {
        if (!current_user_can('edit_user', $user->ID)) {
            return false;
        }
        ?>
        <h3><?php _e('Farmer Profile Information', 'farming-footprints-ai'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="farm_size"><?php _e('Farm Size (acres)', 'farming-footprints-ai'); ?></label></th>
                <td>
                    <input type="number" name="farm_size" id="farm_size" value="<?php echo esc_attr(get_user_meta($user->ID, 'farm_size', true)); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="primary_crops"><?php _e('Primary Crops', 'farming-footprints-ai'); ?></label></th>
                <td>
                    <input type="text" name="primary_crops" id="primary_crops" value="<?php echo esc_attr(get_user_meta($user->ID, 'primary_crops', true)); ?>" class="regular-text" />
                    <p class="description"><?php _e('Enter crops separated by commas', 'farming-footprints-ai'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="farming_method"><?php _e('Farming Method', 'farming-footprints-ai'); ?></label></th>
                <td>
                    <select name="farming_method" id="farming_method">
                        <option value="conventional" <?php selected(get_user_meta($user->ID, 'farming_method', true), 'conventional'); ?>><?php _e('Conventional', 'farming-footprints-ai'); ?></option>
                        <option value="organic" <?php selected(get_user_meta($user->ID, 'farming_method', true), 'organic'); ?>><?php _e('Organic', 'farming-footprints-ai'); ?></option>
                        <option value="biodynamic" <?php selected(get_user_meta($user->ID, 'farming_method', true), 'biodynamic'); ?>><?php _e('Biodynamic', 'farming-footprints-ai'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    public static function save_farmer_profile_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }

        update_user_meta($user_id, 'farm_size', sanitize_text_field($_POST['farm_size']));
        update_user_meta($user_id, 'primary_crops', sanitize_text_field($_POST['primary_crops']));
        update_user_meta($user_id, 'farming_method', sanitize_text_field($_POST['farming_method']));
    }

    public static function register_user($username, $email, $password) {
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            return $user_id;
        }

        self::set_default_user_role($user_id);

        return $user_id;
    }

    public static function login_user($username, $password, $remember = false) {
        $creds = array(
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => $remember
        );

        $user = wp_signon($creds, false);

        if (is_wp_error($user)) {
            return $user;
        }

        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, $remember);

        return $user;
    }

    public static function logout_user() {
        wp_logout();
        wp_safe_redirect(home_url());
        exit();
    }

    public static function reset_password($user_login) {
        $user = get_user_by('login', $user_login);
        if (!$user) {
            $user = get_user_by('email', $user_login);
        }

        if (!$user) {
            return new WP_Error('invalid_user', __('Invalid username or email.', 'farming-footprints-ai'));
        }

        $key = get_password_reset_key($user);
        if (is_wp_error($key)) {
            return $key;
        }

        $message = __('Someone has requested a password reset for the following account:', 'farming-footprints-ai') . "\r\n\r\n";
        $message .= network_home_url('/') . "\r\n\r\n";
        $message .= sprintf(__('Username: %s', 'farming-footprints-ai'), $user->user_login) . "\r\n\r\n";
        $message .= __('If this was a mistake, just ignore this email and nothing will happen.', 'farming-footprints-ai') . "\r\n\r\n";
        $message .= __('To reset your password, visit the following address:', 'farming-footprints-ai') . "\r\n\r\n";
        $message .= network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login');

        $title = sprintf(__('[%s] Password Reset', 'farming-footprints-ai'), get_option('blogname'));

        if (wp_mail($user->user_email, wp_specialchars_decode($title), $message)) {
            return true;
        } else {
            return new WP_Error('email_failed', __('The email could not be sent.', 'farming-footprints-ai'));
        }
    }

    public static function check_user_permissions($required_role = 'ffai_farmer') {
        if (!is_user_logged_in()) {
            return false;
        }

        $user = wp_get_current_user();
        return in_array($required_role, (array) $user->roles);
    }
}

// Initialize the authentication class
add_action('init', array('FFAI_User_Authentication', 'init'));

// Add custom WP-CLI commands for testing user authentication functions
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('ffai register-user', function($args, $assoc_args) {
        if (count($args) < 3) {
            WP_CLI::error('Please provide username, email, and password.');
            return;
        }
        list($username, $email, $password) = $args;
        $result = FFAI_User_Authentication::register_user($username, $email, $password);
        if (is_wp_error($result)) {
            WP_CLI::error($result->get_error_message());
        } else {
            WP_CLI::success("User registered successfully. User ID: $result");
        }
    });

    WP_CLI::add_command('ffai reset-password', function($args, $assoc_args) {
        if (count($args) < 1) {
            WP_CLI::error('Please provide username or email.');
            return;
        }
        $user_login = $args[0];
        $result = FFAI_User_Authentication::reset_password($user_login);
        if (is_wp_error($result)) {
            WP_CLI::error($result->get_error_message());
        } else {
            WP_CLI::success("Password reset email sent successfully.");
        }
    });
}