<?php
// Simple plugin activation script
require_once 'wp-config.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$plugin_path = 'securepressx/securepress-x.php';

echo "Checking plugin status...\n";

// Check if plugin exists
if (!file_exists(WP_PLUGIN_DIR . '/' . $plugin_path)) {
    echo "ERROR: Plugin file not found at: " . WP_PLUGIN_DIR . '/' . $plugin_path . "\n";
    exit(1);
}

// Check if plugin is already active
if (is_plugin_active($plugin_path)) {
    echo "Plugin is already active!\n";
} else {
    echo "Plugin is not active. Attempting to activate...\n";
    
    // Try to activate the plugin
    $result = activate_plugin($plugin_path);
    
    if (is_wp_error($result)) {
        echo "ERROR: Failed to activate plugin: " . $result->get_error_message() . "\n";
        exit(1);
    } else {
        echo "SUCCESS: Plugin activated successfully!\n";
    }
}

// Verify activation
if (is_plugin_active($plugin_path)) {
    echo "✓ Plugin is now active\n";
} else {
    echo "✗ Plugin activation failed\n";
}

// Check if REST API endpoints are now available
echo "\nChecking REST API endpoints...\n";
$rest_url = home_url('/wp-json/securepressx/v1/settings');
echo "Testing: " . $rest_url . "\n";

// Simple test - just check if we can make a request
$response = wp_remote_get($rest_url);
if (is_wp_error($response)) {
    echo "REST API test failed: " . $response->get_error_message() . "\n";
} else {
    $status_code = wp_remote_retrieve_response_code($response);
    echo "REST API response code: " . $status_code . "\n";
    if ($status_code === 200) {
        echo "✓ REST API endpoints are working\n";
    } else {
        echo "✗ REST API endpoints may not be registered\n";
    }
} 