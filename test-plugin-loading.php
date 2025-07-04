<?php
// Test file to check if SecurePress X is loading
if (defined('SECUREPRESS_VERSION')) {
    echo "SecurePress X is loaded! Version: " . SECUREPRESS_VERSION . "\n";
    
    // Check if core class exists
    if (class_exists('SecurePress_Core')) {
        echo "SecurePress_Core class exists\n";
        
        // Check if instance exists
        $instance = SecurePress_Core::get_instance();
        if ($instance) {
            echo "SecurePress_Core instance exists\n";
        } else {
            echo "SecurePress_Core instance does not exist\n";
        }
    } else {
        echo "SecurePress_Core class does not exist\n";
    }
    
    // Check if REST API class exists
    if (class_exists('SecurePress_REST_API')) {
        echo "SecurePress_REST_API class exists\n";
    } else {
        echo "SecurePress_REST_API class does not exist\n";
    }
    
} else {
    echo "SecurePress X is NOT loaded\n";
} 