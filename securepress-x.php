<?php
/**
 * Plugin Name: SecurePress X
 * Plugin URI: https://securepress-x.com
 * Description: Comprehensive protection wrapper for all types of WordPress sites - Advanced security plugin
 * Version: 1.0.0
 * Author: SecurePress Team
 * Text Domain: securepress-x
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants if not already defined
if (!defined('SECUREPRESS_VERSION')) {
    define('SECUREPRESS_VERSION', '1.0.0');
}
if (!defined('SECUREPRESS_PLUGIN_FILE')) {
    define('SECUREPRESS_PLUGIN_FILE', __FILE__);
}
if (!defined('SECUREPRESS_PLUGIN_DIR')) {
    define('SECUREPRESS_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('SECUREPRESS_PLUGIN_URL')) {
    define('SECUREPRESS_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('SECUREPRESS_PLUGIN_BASENAME')) {
    define('SECUREPRESS_PLUGIN_BASENAME', plugin_basename(__FILE__));
}

// Additional constants for compatibility
if (!defined('SECUREPRESS_X_VERSION')) {
    define('SECUREPRESS_X_VERSION', '1.0.0');
}
if (!defined('SECUREPRESS_X_PATH')) {
    define('SECUREPRESS_X_PATH', plugin_dir_path(__FILE__));
}
if (!defined('SECUREPRESS_X_URL')) {
    define('SECUREPRESS_X_URL', plugin_dir_url(__FILE__));
}

/**
 * Apply critical security settings very early
 * This runs before WordPress fully loads
 */
function securepress_apply_early_security_settings() {
    // Get settings from options table
    $settings = get_option('securepress_x_settings', array());
    
    // Check if hardening is enabled
    if (isset($settings['hardening']) && isset($settings['hardening']['file_editor_disabled']) && $settings['hardening']['file_editor_disabled']) {
        if (!defined('DISALLOW_FILE_EDIT')) {
            define('DISALLOW_FILE_EDIT', true);
        }
    }
    
    // Apply file modification restriction if enabled
    if (isset($settings['hardening']) && isset($settings['hardening']['disable_file_mods']) && $settings['hardening']['disable_file_mods']) {
        if (!defined('DISALLOW_FILE_MODS')) {
            define('DISALLOW_FILE_MODS', true);
        }
    }
}

// Run early security settings before WordPress loads
securepress_apply_early_security_settings();

// Minimum requirements check
if (!function_exists('securepress_requirements_check')) {
    function securepress_requirements_check() {
        global $wp_version;
        
        $php_version = phpversion();
        $wp_min_version = '5.0';
        $php_min_version = '7.4';
        
        if (version_compare($php_version, $php_min_version, '<')) {
            deactivate_plugins(SECUREPRESS_PLUGIN_BASENAME);
            wp_die(
                sprintf(
                    /* translators: %1$s: required PHP version, %2$s: current PHP version */
                    __('SecurePress X requires PHP version %1$s or higher. You are running version %2$s.', 'securepress-x'),
                    $php_min_version,
                    $php_version
                )
            );
        }
        
        if (version_compare($wp_version, $wp_min_version, '<')) {
            deactivate_plugins(SECUREPRESS_PLUGIN_BASENAME);
            wp_die(
                sprintf(
                    /* translators: %1$s: required WordPress version, %2$s: current WordPress version */
                    __('SecurePress X requires WordPress version %1$s or higher. You are running version %2$s.', 'securepress-x'),
                    $wp_min_version,
                    $wp_version
                )
            );
        }
    }
}

// Check requirements before loading
securepress_requirements_check();

/**
 * Main plugin initialization function
 * 
 * @return SecurePress_Core
 */
function securepress_init() {
    // Load core class
    require_once SECUREPRESS_PLUGIN_DIR . 'includes/class-core.php';
    return SecurePress_Core::get_instance();
}

// Initialize plugin after WordPress is fully loaded
add_action('plugins_loaded', 'securepress_init');

/**
 * Plugin activation function
 */
function securepress_activate() {
    try {
        // Debug: Only create tables for initial testing
        require_once SECUREPRESS_PLUGIN_DIR . 'includes/class-installer.php';
        
        // Log the activation start
        error_log('SecurePress X: Starting plugin activation');
        
        SecurePress_Installer::activate();
        
        // Flush rewrite rules if hide login is enabled
        flush_rewrite_rules();
        
        error_log('SecurePress X: Plugin activation completed');
        
    } catch (Exception $e) {
        error_log('SecurePress X activation hook error: ' . $e->getMessage());
        wp_die('Error activating SecurePress X: ' . esc_html($e->getMessage()));
    }
}

/**
 * Plugin deactivation function
 */
function securepress_deactivate() {
    // Clear scheduled hooks
    wp_clear_scheduled_hook('securepress_file_integrity_check');
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'securepress_activate');
register_deactivation_hook(__FILE__, 'securepress_deactivate');

/**
 * Plugin uninstall function
 */
function securepress_uninstall() {
    require_once SECUREPRESS_PLUGIN_DIR . 'includes/class-installer.php';
    SecurePress_Installer::uninstall();
}

// Register uninstall hook
register_uninstall_hook(__FILE__, 'securepress_uninstall'); 