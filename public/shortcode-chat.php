<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class FFAI_Shortcode_Chat {

    public static function init() {
        add_shortcode('ffai_chat', array(__CLASS__, 'chat_shortcode'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
        add_action('wp_ajax_ffai_chat_request', array(__CLASS__, 'handle_chat_request'));
        add_action('wp_ajax_nopriv_ffai_chat_request', array(__CLASS__, 'handle_chat_request'));
    }

    public static function chat_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => __('Farming Assistant', 'farming-footprints-ai'),
            'placeholder' => __('Ask me anything about farming...', 'farming-footprints-ai'),
        ), $atts, 'ffai_chat');

        ob_start();
        ?>
        <div id="ffai-chat-container" class="ffai-chat-container">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <div id="ffai-chat-messages" class="ffai-chat-messages"></div>
            <form id="ffai-chat-form" class="ffai-chat-form">
                <input type="text" id="ffai-chat-input" class="ffai-chat-input" placeholder="<?php echo esc_attr($atts['placeholder']); ?>">
                <button type="submit" class="ffai-chat-submit"><?php _e('Send', 'farming-footprints-ai'); ?></button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function enqueue_scripts() {
        wp_enqueue_style('ffai-chat-style', FFAI_PLUGIN_URL . 'public/css/chat.css', array(), FFAI_VERSION);
        wp_enqueue_script('ffai-chat-script', FFAI_PLUGIN_URL . 'public/js/chat.js', array('jquery'), FFAI_VERSION, true);
        wp_localize_script('ffai-chat-script', 'ffai_chat_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ffai_chat_nonce'),
        ));
    }

    public static function handle_chat_request() {
        check_ajax_referer('ffai_chat_nonce', 'nonce');

        $user_message = sanitize_text_field($_POST['message']);
        $user_id = get_current_user_id();

        // Process the user's message
        $processed_data = FFAI_Language_Processing::process_user_query($user_message, $user_id);

        // Generate a response
        $ai_response = FFAI_Language_Processing::generate_response($processed_data);

        // Log the interaction
        self::log_interaction($user_id, $user_message, $ai_response, $processed_data['intent']);

        // Return the response
        wp_send_json_success($ai_response);
    }

    private static function log_interaction($user_id, $query, $response, $intent) {
        FFAI_Database_Manager::insert('ai_interactions', array(
            'user_id' => $user_id,
            'query' => $query,
            'response' => $response,
            'intent' => $intent,
        ));
    }
}

FFAI_Shortcode_Chat::init();