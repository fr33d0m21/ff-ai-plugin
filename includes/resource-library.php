<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class FFAI_Resource_Library {

    public static function get_resource($topic) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ffai_resources';

        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE topic = %s",
            $topic
        ), ARRAY_A);

        if (!$result) {
            return new WP_Error('ffai_resource_error', __('Resource not found for this topic.', 'farming-footprints-ai'));
        }

        return $result;
    }

    public static function get_resources_by_category($category) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ffai_resources';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE category = %s ORDER BY title ASC",
            $category
        ), ARRAY_A);

        if (empty($results)) {
            return new WP_Error('ffai_resource_error', __('No resources found in this category.', 'farming-footprints-ai'));
        }

        return $results;
    }

    public static function add_resource($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ffai_resources';

        $result = $wpdb->insert(
            $table_name,
            [
                'title' => $data['title'],
                'content' => $data['content'],
                'topic' => $data['topic'],
                'category' => $data['category'],
                'author' => $data['author'],
                'publication_date' => current_time('mysql'),
                'last_updated' => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        if ($result === false) {
            return new WP_Error('ffai_resource_error', __('Failed to add resource.', 'farming-footprints-ai'));
        }

        return $wpdb->insert_id;
    }

    public static function update_resource($id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ffai_resources';

        $result = $wpdb->update(
            $table_name,
            [
                'title' => $data['title'],
                'content' => $data['content'],
                'topic' => $data['topic'],
                'category' => $data['category'],
                'last_updated' => current_time('mysql'),
            ],
            ['id' => $id],
            ['%s', '%s', '%s', '%s', '%s'],
            ['%d']
        );

        if ($result === false) {
            return new WP_Error('ffai_resource_error', __('Failed to update resource.', 'farming-footprints-ai'));
        }

        return true;
    }

    public static function delete_resource($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ffai_resources';

        $result = $wpdb->delete(
            $table_name,
            ['id' => $id],
            ['%d']
        );

        if ($result === false) {
            return new WP_Error('ffai_resource_error', __('Failed to delete resource.', 'farming-footprints-ai'));
        }

        return true;
    }

    public static function search_resources($query) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ffai_resources';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE 
            title LIKE %s OR 
            content LIKE %s OR 
            topic LIKE %s OR 
            category LIKE %s 
            ORDER BY title ASC",
            '%' . $wpdb->esc_like($query) . '%',
            '%' . $wpdb->esc_like($query) . '%',
            '%' . $wpdb->esc_like($query) . '%',
            '%' . $wpdb->esc_like($query) . '%'
        ), ARRAY_A);

        if (empty($results)) {
            return new WP_Error('ffai_resource_error', __('No resources found matching the search query.', 'farming-footprints-ai'));
        }

        return $results;
    }

    public static function get_recent_resources($limit = 5) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ffai_resources';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY publication_date DESC LIMIT %d",
            $limit
        ), ARRAY_A);

        if (empty($results)) {
            return new WP_Error('ffai_resource_error', __('No recent resources found.', 'farming-footprints-ai'));
        }

        return $results;
    }

    public static function get_resource_categories() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ffai_resources';

        $results = $wpdb->get_col("SELECT DISTINCT category FROM $table_name ORDER BY category ASC");

        if (empty($results)) {
            return new WP_Error('ffai_resource_error', __('No resource categories found.', 'farming-footprints-ai'));
        }

        return $results;
    }

    public static function import_resources_from_csv($file_path) {
        if (!file_exists($file_path)) {
            return new WP_Error('ffai_resource_error', __('CSV file not found.', 'farming-footprints-ai'));
        }

        $file = fopen($file_path, 'r');
        $header = fgetcsv($file); // Assume first row is header

        $imported_count = 0;
        while (($data = fgetcsv($file)) !== FALSE) {
            $resource_data = array_combine($header, $data);
            $result = self::add_resource($resource_data);
            if (!is_wp_error($result)) {
                $imported_count++;
            }
        }

        fclose($file);

        return $imported_count;
    }
}

// Add custom WP-CLI commands for testing resource library functions
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('ffai get-resource', function($args, $assoc_args) {
        if (count($args) < 1) {
            WP_CLI::error('Please provide a resource topic.');
            return;
        }
        $topic = $args[0];
        $result = FFAI_Resource_Library::get_resource($topic);
        if (is_wp_error($result)) {
            WP_CLI::error($result->get_error_message());
        } else {
            WP_CLI::success("Resource found: " . json_encode($result));
        }
    });

    WP_CLI::add_command('ffai search-resources', function($args, $assoc_args) {
        if (count($args) < 1) {
            WP_CLI::error('Please provide a search query.');
            return;
        }
        $query = $args[0];
        $result = FFAI_Resource_Library::search_resources($query);
        if (is_wp_error($result)) {
            WP_CLI::error($result->get_error_message());
        } else {
            WP_CLI::success("Search results: " . json_encode($result));
        }
    });

    WP_CLI::add_command('ffai import-resources', function($args, $assoc_args) {
        if (count($args) < 1) {
            WP_CLI::error('Please provide the path to the CSV file.');
            return;
        }
        $file_path = $args[0];
        $result = FFAI_Resource_Library::import_resources_from_csv($file_path);
        if (is_wp_error($result)) {
            WP_CLI::error($result->get_error_message());
        } else {
            WP_CLI::success("Imported $result resources successfully.");
        }
    });
}