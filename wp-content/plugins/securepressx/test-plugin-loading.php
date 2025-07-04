<?php
// Test file to check if SecurePress X is loading
// Load WordPress
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

echo "<h1>SecurePress X Plugin Loading Test</h1>";

if (defined('SECUREPRESS_VERSION')) {
    echo "<p style='color: green;'>✓ SecurePress X is loaded! Version: " . SECUREPRESS_VERSION . "</p>";
    
    // Check if core class exists
    if (class_exists('SecurePress_Core')) {
        echo "<p style='color: green;'>✓ SecurePress_Core class exists</p>";
        
        // Check if instance exists
        $instance = SecurePress_Core::get_instance();
        if ($instance) {
            echo "<p style='color: green;'>✓ SecurePress_Core instance exists</p>";
        } else {
            echo "<p style='color: red;'>✗ SecurePress_Core instance does not exist</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ SecurePress_Core class does not exist</p>";
    }
    
    // Check if REST API class exists
    if (class_exists('SecurePress_REST_API')) {
        echo "<p style='color: green;'>✓ SecurePress_REST_API class exists</p>";
    } else {
        echo "<p style='color: red;'>✗ SecurePress_REST_API class does not exist</p>";
    }
    
    // Check active plugins
    echo "<h2>Active Plugins:</h2>";
    $active_plugins = get_option('active_plugins', array());
    echo "<ul>";
    foreach ($active_plugins as $plugin) {
        echo "<li>" . $plugin . "</li>";
    }
    echo "</ul>";
    
} else {
    echo "<p style='color: red;'>✗ SecurePress X is NOT loaded</p>";
    
    // Check if plugin file exists
    $plugin_file = __DIR__ . '/securepress-x.php';
    if (file_exists($plugin_file)) {
        echo "<p style='color: orange;'>! Plugin file exists but not loaded</p>";
    } else {
        echo "<p style='color: red;'>! Plugin file does not exist</p>";
    }
    
    // Check active plugins
    echo "<h2>Active Plugins:</h2>";
    $active_plugins = get_option('active_plugins', array());
    echo "<ul>";
    foreach ($active_plugins as $plugin) {
        echo "<li>" . $plugin . "</li>";
    }
    echo "</ul>";
} 