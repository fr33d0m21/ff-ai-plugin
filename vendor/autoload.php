<?php
/**
 * Farming Footprints AI Autoloader
 *
 * @package FarmingFootprintsAI
 */

spl_autoload_register(function ($class) {
    // Project-specific namespace prefix
    $prefix = 'FarmingFootprintsAI\\';

    // Base directory for the namespace prefix
    $base_dir = dirname(__DIR__) . '/includes/';

    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // No, move to the next registered autoloader
        return;
    }

    // Get the relative class name
    $relative_class = substr($class, $len);

    // Replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// Load any global functions
require_once dirname(__DIR__) . '/includes/functions.php';

// Load Composer's autoloader if it exists (for third-party libraries)
$composer_autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($composer_autoload)) {
    require_once $composer_autoload;
}