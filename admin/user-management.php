<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

function ffai_user_management_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'farming-footprints-ai'));
    }

    $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';

    ?>
    <div class="wrap">
        <h1><?php _e('User Management', 'farming-footprints-ai'); ?></h1>

        <?php
        switch ($action) {
            case 'add':
                ffai_add_user_form();
                break;
            case 'edit':
                ffai_edit_user_form();
                break;
            case 'view':
                ffai_view_user_details();
                break;
            default:
                ffai_list_users();
                break;
        }
        ?>
    </div>
    <?php
}

function ffai_list_users() {
    $users = get_users(array('role__in' => array('subscriber', 'ffai_farmer')));
    ?>
    <h2><?php _e('Farming Footprints AI Users', 'farming-footprints-ai'); ?></h2>
    <a href="?page=ffai-user-management&action=add" class="button button-primary"><?php _e('Add New User', 'farming-footprints-ai'); ?></a>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Username', 'farming-footprints-ai'); ?></th>
                <th><?php _e('Email', 'farming-footprints-ai'); ?></th>
                <th><?php _e('Role', 'farming-footprints-ai'); ?></th>
                <th><?php _e('Last Login', 'farming-footprints-ai'); ?></th>
                <th><?php _e('Actions', 'farming-footprints-ai'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user) : ?>
                <tr>
                    <td><?php echo esc_html($user->user_login); ?></td>
                    <td><?php echo esc_html($user->user_email); ?></td>
                    <td><?php echo esc_html(ucfirst($user->roles[0])); ?></td>
                    <td><?php echo esc_html(get_user_meta($user->ID, 'last_login', true)); ?></td>
                    <td>
                        <a href="?page=ffai-user-management&action=view&user_id=<?php echo $user->ID; ?>"><?php _e('View', 'farming-footprints-ai'); ?></a> |
                        <a href="?page=ffai-user-management&action=edit&user_id=<?php echo $user->ID; ?>"><?php _e('Edit', 'farming-footprints-ai'); ?></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function ffai_add_user_form() {
    if (isset($_POST['ffai_add_user'])) {
        check_admin_referer('ffai_add_user_nonce');
        ffai_process_add_user();
    }
    ?>
    <h2><?php _e('Add New User', 'farming-footprints-ai'); ?></h2>
    <form method="post" action="">
        <?php wp_nonce_field('ffai_add_user_nonce'); ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="username"><?php _e('Username', 'farming-footprints-ai'); ?></label></th>
                <td><input type="text" name="username" id="username" class="regular-text" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="email"><?php _e('Email', 'farming-footprints-ai'); ?></label></th>
                <td><input type="email" name="email" id="email" class="regular-text" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="password"><?php _e('Password', 'farming-footprints-ai'); ?></label></th>
                <td><input type="password" name="password" id="password" class="regular-text" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="role"><?php _e('Role', 'farming-footprints-ai'); ?></label></th>
                <td>
                    <select name="role" id="role">
                        <option value="subscriber"><?php _e('Subscriber', 'farming-footprints-ai'); ?></option>
                        <option value="ffai_farmer"><?php _e('Farmer', 'farming-footprints-ai'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" name="ffai_add_user" class="button button-primary" value="<?php _e('Add User', 'farming-footprints-ai'); ?>">
        </p>
    </form>
    <?php
}

function ffai_process_add_user() {
    $username = sanitize_user($_POST['username']);
    $email = sanitize_email($_POST['email']);
    $password = $_POST['password'];
    $role = sanitize_text_field($_POST['role']);

    $user_id = wp_create_user($username, $password, $email);

    if (is_wp_error($user_id)) {
        wp_die($user_id->get_error_message());
    }

    $user = new WP_User($user_id);
    $user->set_role($role);

    wp_redirect(admin_url('admin.php?page=ffai-user-management&message=user_added'));
    exit;
}

function ffai_edit_user_form() {
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    $user = get_userdata($user_id);

    if (!$user) {
        wp_die(__('User not found.', 'farming-footprints-ai'));
    }

    if (isset($_POST['ffai_edit_user'])) {
        check_admin_referer('ffai_edit_user_nonce');
        ffai_process_edit_user($user_id);
    }
    ?>
    <h2><?php _e('Edit User', 'farming-footprints-ai'); ?></h2>
    <form method="post" action="">
        <?php wp_nonce_field('ffai_edit_user_nonce'); ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="email"><?php _e('Email', 'farming-footprints-ai'); ?></label></th>
                <td><input type="email" name="email" id="email" value="<?php echo esc_attr($user->user_email); ?>" class="regular-text" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="role"><?php _e('Role', 'farming-footprints-ai'); ?></label></th>
                <td>
                    <select name="role" id="role">
                        <option value="subscriber" <?php selected($user->roles[0], 'subscriber'); ?>><?php _e('Subscriber', 'farming-footprints-ai'); ?></option>
                        <option value="ffai_farmer" <?php selected($user->roles[0], 'ffai_farmer'); ?>><?php _e('Farmer', 'farming-footprints-ai'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" name="ffai_edit_user" class="button button-primary" value="<?php _e('Update User', 'farming-footprints-ai'); ?>">
        </p>
    </form>
    <?php
}

function ffai_process_edit_user($user_id) {
    $email = sanitize_email($_POST['email']);
    $role = sanitize_text_field($_POST['role']);

    wp_update_user(array(
        'ID' => $user_id,
        'user_email' => $email
    ));

    $user = new WP_User($user_id);
    $user->set_role($role);

    wp_redirect(admin_url('admin.php?page=ffai-user-management&message=user_updated'));
    exit;
}

function ffai_view_user_details() {
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    $user = get_userdata($user_id);

    if (!$user) {
        wp_die(__('User not found.', 'farming-footprints-ai'));
    }

    $farm_data = get_user_meta($user_id, 'ffai_farm_data', true);
    $ai_interactions = get_user_meta($user_id, 'ffai_ai_interactions', true);
    ?>
    <h2><?php printf(__('User Details: %s', 'farming-footprints-ai'), esc_html($user->user_login)); ?></h2>
    <table class="form-table">
        <tr>
            <th scope="row"><?php _e('Email', 'farming-footprints-ai'); ?></th>
            <td><?php echo esc_html($user->user_email); ?></td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Role', 'farming-footprints-ai'); ?></th>
            <td><?php echo esc_html(ucfirst($user->roles[0])); ?></td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Registration Date', 'farming-footprints-ai'); ?></th>
            <td><?php echo esc_html($user->user_registered); ?></td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Last Login', 'farming-footprints-ai'); ?></th>
            <td><?php echo esc_html(get_user_meta($user_id, 'last_login', true)); ?></td>
        </tr>
    </table>

    <h3><?php _e('Farm Data', 'farming-footprints-ai'); ?></h3>
    <?php if ($farm_data) : ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Farm Size', 'farming-footprints-ai'); ?></th>
                <td><?php echo esc_html($farm_data['size']); ?> <?php _e('acres', 'farming-footprints-ai'); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Primary Crops', 'farming-footprints-ai'); ?></th>
                <td><?php echo esc_html(implode(', ', $farm_data['crops'])); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Farming Method', 'farming-footprints-ai'); ?></th>
                <td><?php echo esc_html($farm_data['method']); ?></td>
            </tr>
        </table>
    <?php else : ?>
        <p><?php _e('No farm data available.', 'farming-footprints-ai'); ?></p>
    <?php endif; ?>

    <h3><?php _e('AI Interactions', 'farming-footprints-ai'); ?></h3>
    <?php if ($ai_interactions) : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Date', 'farming-footprints-ai'); ?></th>
                    <th><?php _e('Query', 'farming-footprints-ai'); ?></th>
                    <th><?php _e('Response Summary', 'farming-footprints-ai'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ai_interactions as $interaction) : ?>
                    <tr>
                        <td><?php echo esc_html($interaction['date']); ?></td>
                        <td><?php echo esc_html($interaction['query']); ?></td>
                        <td><?php echo esc_html(substr($interaction['response'], 0, 100) . '...'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p><?php _e('No AI interactions recorded.', 'farming-footprints-ai'); ?></p>
    <?php endif; ?>
    <?php
}

// Hook to record user login
add_action('wp_login', 'ffai_record_user_login', 10, 2);

function ffai_record_user_login($user_login, $user) {
    update_user_meta($user->ID, 'last_login', current_time('mysql'));
}

// Add custom user role
add_action('init', 'ffai_add_custom_roles');

function ffai_add_custom_roles() {
    add_role('ffai_farmer', __('Farmer', 'farming-footprints-ai'), array(
        'read' => true,
        'edit_posts' => false,
        'delete_posts' => false,
    ));
}