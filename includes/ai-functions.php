<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class FFAI_AI_Functions {

    private static $api_url = 'https://api.anthropic.com/v1/messages';

    public static function chat_with_ai($messages, $user_id = null) {
        $api_key = get_option('ffai_api_key', '');
        $model_version = get_option('ffai_model_version', 'claude-3-sonnet-20240229');

        if (empty($api_key)) {
            return new WP_Error('ffai_config_error', __('AI configuration is incomplete.', 'farming-footprints-ai'));
        }

        $system_message = self::get_system_message($user_id);
        array_unshift($messages, ['role' => 'system', 'content' => $system_message]);

        $request_body = [
            'model' => $model_version,
            'messages' => $messages,
            'max_tokens' => 1024,
            'temperature' => 0.7
        ];

        $response = wp_remote_post(self::$api_url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01'
            ],
            'body' => json_encode($request_body),
            'timeout' => 60
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['content'][0]['text'])) {
            $ai_response = $data['content'][0]['text'];
            self::log_interaction($user_id, end($messages)['content'], $ai_response);
            return self::process_ai_response($ai_response);
        } else {
            return new WP_Error('ffai_response_error', __('Unexpected AI response format.', 'farming-footprints-ai'));
        }
    }

    private static function get_system_message($user_id = null) {
        $base_message = "You are an AI assistant for Farming Footprints, specializing in sustainable agriculture and AI-driven farming practices.";
        
        if ($user_id) {
            $farm_data = get_user_meta($user_id, 'ffai_farm_data', true);
            if ($farm_data) {
                $base_message .= " The user has a farm of {$farm_data['size']} acres, primarily growing " . implode(', ', $farm_data['crops']) . 
                                 ". Their farming method is {$farm_data['method']}.";
            }
        }

        return $base_message;
    }

    public static function analyze_image($image_url, $user_query, $user_id = null) {
        $api_key = get_option('ffai_api_key', '');
        $model_version = get_option('ffai_model_version', 'claude-3-sonnet-20240229');

        if (empty($api_key)) {
            return new WP_Error('ffai_config_error', __('AI configuration is incomplete.', 'farming-footprints-ai'));
        }

        $system_message = "You are an expert in agricultural image analysis. Analyze the provided image and answer the user's query about it.";

        $request_body = [
            'model' => $model_version,
            'messages' => [
                ['role' => 'system', 'content' => $system_message],
                ['role' => 'user', 'content' => [
                    ['type' => 'image', 'source' => ['type' => 'url', 'url' => $image_url]],
                    ['type' => 'text', 'text' => $user_query]
                ]]
            ],
            'max_tokens' => 1024
        ];

        $response = wp_remote_post(self::$api_url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01'
            ],
            'body' => json_encode($request_body),
            'timeout' => 60
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['content'][0]['text'])) {
            $ai_response = $data['content'][0]['text'];
            self::log_interaction($user_id, $user_query, $ai_response, 'image_analysis');
            return self::process_ai_response($ai_response);
        } else {
            return new WP_Error('ffai_response_error', __('Unexpected AI response format.', 'farming-footprints-ai'));
        }
    }

    private static function process_ai_response($response) {
        // Process special commands or tags in the AI response
        $response = preg_replace_callback(
            '/\[WEATHER:(\w+)\]/',
            function($matches) {
                $location = $matches[1];
                $weather_data = FFAI_Weather_Integration::get_weather_data($location);
                return $weather_data ? "Current weather in $location: " . $weather_data['description'] : "[Weather data unavailable]";
            },
            $response
        );

        $response = preg_replace_callback(
            '/\[CROP_CALENDAR:(\w+)\]/',
            function($matches) {
                $crop = $matches[1];
                $calendar = FFAI_Crop_Calendar::get_crop_calendar($crop);
                return $calendar ? "Crop calendar for $crop: Planting - {$calendar['plant']}, Harvest - {$calendar['harvest']}" : "[Crop calendar unavailable]";
            },
            $response
        );

        return $response;
    }

    private static function log_interaction($user_id, $query, $response, $type = 'chat') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ffai_interactions';

        $wpdb->insert(
            $table_name,
            [
                'user_id' => $user_id,
                'interaction_type' => $type,
                'query' => $query,
                'response' => $response,
                'interaction_date' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s', '%s']
        );
    }

    public static function get_farming_recommendation($crop, $location, $user_id = null) {
        $weather_data = FFAI_Weather_Integration::get_weather_forecast($location);
        $crop_data = FFAI_Crop_Calendar::get_crop_data($crop);

        $query = "Based on the following information, provide a farming recommendation for $crop in $location:\n";
        $query .= "Weather forecast: " . json_encode($weather_data) . "\n";
        $query .= "Crop data: " . json_encode($crop_data) . "\n";
        $query .= "What actions should the farmer take in the coming week?";

        $messages = [
            ['role' => 'user', 'content' => $query]
        ];

        return self::chat_with_ai($messages, $user_id);
    }

    public static function perform_model_update() {
        // This function would handle any necessary model updates or fine-tuning
        // For now, we'll just log that the function was called
        error_log('FFAI: perform_model_update called at ' . current_time('mysql'));
        return true;
    }
}

// Add a custom WP-CLI command for testing AI functions
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('ffai test-ai', function($args, $assoc_args) {
        $query = isset($args[0]) ? $args[0] : 'Tell me about sustainable farming practices.';
        $result = FFAI_AI_Functions::chat_with_ai([['role' => 'user', 'content' => $query]]);
        if (is_wp_error($result)) {
            WP_CLI::error($result->get_error_message());
        } else {
            WP_CLI::success("AI Response: " . $result);
        }
    });
}