<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class FFAI_Language_Processing {

    private static $supported_languages = ['en', 'es', 'fr', 'de', 'zh']; // English, Spanish, French, German, Chinese

    public static function process_user_query($query, $user_id = null) {
        // Preprocess the query
        $processed_query = self::preprocess_text($query);

        // Identify intent and entities
        $intent = self::identify_intent($processed_query);
        $entities = self::extract_entities($processed_query);

        // Get user context if user_id is provided
        $context = $user_id ? self::get_user_context($user_id) : [];

        // Combine all information
        $processed_data = [
            'original_query' => $query,
            'processed_query' => $processed_query,
            'intent' => $intent,
            'entities' => $entities,
            'context' => $context
        ];

        return $processed_data;
    }

    private static function preprocess_text($text) {
        // Convert to lowercase
        $text = strtolower($text);

        // Remove punctuation
        $text = preg_replace('/[^\w\s]/', '', $text);

        // Remove extra whitespace
        $text = preg_replace('/\s+/', ' ', trim($text));

        return $text;
    }

    private static function identify_intent($query) {
        $intent_patterns = [
            'weather' => '/weather|forecast|temperature|rain|humidity/',
            'crop_info' => '/crop|plant|grow|cultivate|harvest/',
            'pest_control' => '/pest|disease|insect|fungus|weed/',
            'soil_management' => '/soil|fertilizer|nutrient|pH|organic matter/',
            'irrigation' => '/water|irrigate|moisture|drought/',
            'market_prices' => '/price|market|sell|trade|export/'
        ];

        foreach ($intent_patterns as $intent => $pattern) {
            if (preg_match($pattern, $query)) {
                return $intent;
            }
        }

        return 'general_inquiry';
    }

    private static function extract_entities($query) {
        $entities = [];

        // Extract crop names
        $crop_pattern = '/\b(corn|wheat|soybean|rice|potato|tomato)\b/';
        if (preg_match_all($crop_pattern, $query, $matches)) {
            $entities['crops'] = $matches[0];
        }

        // Extract locations
        $location_pattern = '/\b([A-Z][a-z]+ ?[A-Z]?[a-z]*)\b/';
        if (preg_match_all($location_pattern, $query, $matches)) {
            $entities['locations'] = $matches[0];
        }

        // Extract dates or time periods
        $date_pattern = '/\b(\d{4}|\d{1,2}\/\d{1,2}\/\d{4}|january|february|march|april|may|june|july|august|september|october|november|december)\b/i';
        if (preg_match_all($date_pattern, $query, $matches)) {
            $entities['dates'] = $matches[0];
        }

        return $entities;
    }

    private static function get_user_context($user_id) {
        $user_meta = get_user_meta($user_id);
        return [
            'farm_size' => $user_meta['farm_size'][0] ?? null,
            'primary_crops' => $user_meta['primary_crops'][0] ?? null,
            'farming_method' => $user_meta['farming_method'][0] ?? null,
            'location' => $user_meta['location'][0] ?? null
        ];
    }

    public static function translate_content($content, $target_language) {
        if (!in_array($target_language, self::$supported_languages)) {
            return new WP_Error('unsupported_language', __('The target language is not supported.', 'farming-footprints-ai'));
        }

        // In a real-world scenario, you would integrate with a translation API here
        // For demonstration purposes, we'll use a simple mock translation
        $translated_content = self::mock_translate($content, $target_language);

        return $translated_content;
    }

    private static function mock_translate($content, $target_language) {
        // This is a very simplistic mock translation for demonstration purposes
        $translations = [
            'es' => [
                'Hello' => 'Hola',
                'Weather' => 'Clima',
                'Crop' => 'Cultivo',
                'Farmer' => 'Agricultor'
            ],
            'fr' => [
                'Hello' => 'Bonjour',
                'Weather' => 'Météo',
                'Crop' => 'Culture',
                'Farmer' => 'Agriculteur'
            ],
            // Add more languages and translations as needed
        ];

        if (isset($translations[$target_language])) {
            return str_replace(
                array_keys($translations[$target_language]),
                array_values($translations[$target_language]),
                $content
            );
        }

        return $content; // Return original content if no translation is available
    }

    public static function generate_response($processed_data) {
        $intent = $processed_data['intent'];
        $entities = $processed_data['entities'];
        $context = $processed_data['context'];

        switch ($intent) {
            case 'weather':
                return self::generate_weather_response($entities, $context);
            case 'crop_info':
                return self::generate_crop_info_response($entities, $context);
            case 'pest_control':
                return self::generate_pest_control_response($entities, $context);
            case 'soil_management':
                return self::generate_soil_management_response($entities, $context);
            case 'irrigation':
                return self::generate_irrigation_response($entities, $context);
            case 'market_prices':
                return self::generate_market_prices_response($entities, $context);
            default:
                return __("I'm not sure how to respond to that query. Could you please rephrase or ask about weather, crops, pests, soil, irrigation, or market prices?", 'farming-footprints-ai');
        }
    }

    private static function generate_weather_response($entities, $context) {
        $location = $entities['locations'][0] ?? $context['location'] ?? 'your area';
        return sprintf(__("Here's the weather forecast for %s: [Insert actual weather data here]", 'farming-footprints-ai'), $location);
    }

    private static function generate_crop_info_response($entities, $context) {
        $crop = $entities['crops'][0] ?? $context['primary_crops'] ?? 'your crops';
        return sprintf(__("Here's some information about growing %s: [Insert crop information here]", 'farming-footprints-ai'), $crop);
    }

    private static function generate_pest_control_response($entities, $context) {
        $crop = $entities['crops'][0] ?? $context['primary_crops'] ?? 'your crops';
        return sprintf(__("Here are some pest control tips for %s: [Insert pest control information here]", 'farming-footprints-ai'), $crop);
    }

    private static function generate_soil_management_response($entities, $context) {
        $method = $context['farming_method'] ?? 'general';
        return sprintf(__("Here are some soil management tips for %s farming: [Insert soil management information here]", 'farming-footprints-ai'), $method);
    }

    private static function generate_irrigation_response($entities, $context) {
        $farm_size = $context['farm_size'] ?? 'your farm';
        return sprintf(__("Here are some irrigation recommendations for a %s acre farm: [Insert irrigation information here]", 'farming-footprints-ai'), $farm_size);
    }

    private static function generate_market_prices_response($entities, $context) {
        $crop = $entities['crops'][0] ?? $context['primary_crops'] ?? 'common crops';
        return sprintf(__("Here are the current market prices for %s: [Insert market price information here]", 'farming-footprints-ai'), $crop);
    }
}

// Add custom WP-CLI commands for testing language processing functions
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('ffai process-query', function($args, $assoc_args) {
        if (count($args) < 1) {
            WP_CLI::error('Please provide a query to process.');
            return;
        }
        $query = implode(' ', $args);
        $user_id = isset($assoc_args['user_id']) ? intval($assoc_args['user_id']) : null;
        $result = FFAI_Language_Processing::process_user_query($query, $user_id);
        WP_CLI::log("Processed Query Data:");
        WP_CLI::log(json_encode($result, JSON_PRETTY_PRINT));

        $response = FFAI_Language_Processing::generate_response($result);
        WP_CLI::success("Generated Response:");
        WP_CLI::log($response);
    });

    WP_CLI::add_command('ffai translate', function($args, $assoc_args) {
        if (count($args) < 2) {
            WP_CLI::error('Please provide the text to translate and the target language code.');
            return;
        }
        $text = $args[0];
        $target_language = $args[1];
        $result = FFAI_Language_Processing::translate_content($text, $target_language);
        if (is_wp_error($result)) {
            WP_CLI::error($result->get_error_message());
        } else {
            WP_CLI::success("Translated text: $result");
        }
    });
}