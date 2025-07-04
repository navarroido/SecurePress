<?php
// Check and activate SecurePress X plugin
define('WP_USE_THEMES', false);
require_once 'wp-load.php';

$plugin_file = 'securepressx/securepress-x.php';

echo "Plugin Status Check\n";
echo "==================\n\n";

// Check if plugin file exists
$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
if (file_exists($plugin_path)) {
    echo "✓ Plugin file exists: $plugin_path\n";
} else {
    echo "✗ Plugin file not found: $plugin_path\n";
    exit;
}

// Get active plugins
$active_plugins = get_option('active_plugins', array());
echo "Active plugins count: " . count($active_plugins) . "\n";

// Check if SecurePress X is active
if (in_array($plugin_file, $active_plugins)) {
    echo "✓ SecurePress X is ACTIVE\n";
} else {
    echo "✗ SecurePress X is NOT ACTIVE\n";
    
    // Try to activate it
    echo "Attempting to activate...\n";
    
    if (!function_exists('activate_plugin')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    $result = activate_plugin($plugin_file);
    
    if (is_wp_error($result)) {
        echo "✗ Activation failed: " . $result->get_error_message() . "\n";
    } else {
        echo "✓ Plugin activated successfully!\n";
    }
}

// List all active plugins
echo "\nAll active plugins:\n";
foreach ($active_plugins as $plugin) {
    echo "- $plugin\n";
}

// Check if SecurePress class exists
if (class_exists('SecurePress')) {
    echo "\n✓ SecurePress class is loaded\n";
} else {
    echo "\n✗ SecurePress class is NOT loaded\n";
}

// Check for fatal errors in the plugin
echo "\nChecking for recent errors...\n";
$debug_log = WP_CONTENT_DIR . '/debug.log';
if (file_exists($debug_log)) {
    $log_content = file_get_contents($debug_log);
    $lines = explode("\n", $log_content);
    $recent_lines = array_slice($lines, -20);
    
    $has_fatal = false;
    foreach ($recent_lines as $line) {
        if (strpos($line, 'Fatal error') !== false && strpos($line, 'securepressx') !== false) {
            echo "✗ Fatal error found: $line\n";
            $has_fatal = true;
        }
    }
    
    if (!$has_fatal) {
        echo "✓ No recent fatal errors found\n";
    }
} else {
    echo "No debug log found\n";
} 