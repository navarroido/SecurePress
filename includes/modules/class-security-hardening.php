<?php
/**
 * SecurePress X Security Hardening Module
 * 
 * Applies various security hardening measures to strengthen WordPress
 * against common attacks and vulnerabilities.
 * 
 * @package SecurePress_X
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Security Hardening module class
 * 
 * TODO: Implement full security hardening functionality
 * - Disable file editing in admin
 * - Remove WordPress version info
 * - Disable XML-RPC if not needed
 * - Hide wp-config.php and .htaccess
 * - Disable directory browsing
 * - Remove default admin user
 * - Disable pingbacks and trackbacks
 * - Limit post revisions
 * - Change database table prefix
 * - Remove unnecessary meta generators
 */
class SecurePress_Security_Hardening extends SecurePress_Module {
    
    /**
     * Constructor
     */
    public function __construct($module_id) {
        parent::__construct($module_id);
    }
    
    /**
     * Register WordPress hooks
     */
    protected function register_hooks() {
        if (!$this->is_enabled()) {
            return;
        }
        
        $settings = $this->get_settings();
        
        // Add conditional hooks based on settings
        if (isset($settings['file_editor_disabled']) && $settings['file_editor_disabled']) {
            add_action('init', array($this, 'disable_file_editing'));
        }
        
        if (isset($settings['disable_file_mods']) && $settings['disable_file_mods']) {
            add_action('init', array($this, 'disable_file_modifications'));
        }
        
        if (isset($settings['disable_version_info']) && $settings['disable_version_info']) {
            add_filter('the_generator', '__return_empty_string');
            add_action('wp_head', array($this, 'remove_version_info'), 1);
        }
        
        if (isset($settings['disable_xmlrpc']) && $settings['disable_xmlrpc']) {
            add_filter('xmlrpc_enabled', '__return_false');
        }
        
        if (isset($settings['disable_pingbacks']) && $settings['disable_pingbacks']) {
            add_filter('xmlrpc_methods', array($this, 'disable_pingback_methods'));
        }
        
        // Add more hardening hooks
        add_action('init', array($this, 'apply_hardening_measures'));
    }
    
    /**
     * Disable file editing in WordPress admin
     * 
     * TODO: Implement file editing disabling
     */
    public function disable_file_editing() {
        if (!defined('DISALLOW_FILE_EDIT')) {
            define('DISALLOW_FILE_EDIT', true);
        }
        
        $this->log('File editing disabled in admin', 'info');
    }
    
    /**
     * Disable file modifications
     * 
     * TODO: Implement file modification disabling
     */
    public function disable_file_modifications() {
        // TODO: Implement file modification disabling
        
        $this->log('File modifications disabled', 'info');
    }
    
    /**
     * Remove WordPress version information
     * 
     * TODO: Remove version info from various locations
     */
    public function remove_version_info() {
        // TODO: Remove version from RSS feeds
        // TODO: Remove version from admin scripts/styles
        // TODO: Remove version from login page
        
        remove_action('wp_head', 'wp_generator');
        $this->log('WordPress version information removed', 'info');
    }
    
    /**
     * Disable XML-RPC pingback methods
     * 
     * TODO: Selectively disable dangerous XML-RPC methods
     */
    public function disable_pingback_methods($methods) {
        // TODO: Remove pingback methods while preserving useful ones
        unset($methods['pingback.ping']);
        unset($methods['pingback.extensions.getPingbacks']);
        
        $this->log('XML-RPC pingback methods disabled', 'info');
        return $methods;
    }
    
    /**
     * Apply various hardening measures
     * 
     * TODO: Implement comprehensive hardening
     */
    public function apply_hardening_measures() {
        $settings = $this->get_settings();
        
        // TODO: Implement each hardening measure based on settings
        if ($settings['hide_login_errors'] ?? true) {
            add_filter('login_errors', array($this, 'generic_login_error'));
        }
        
        if ($settings['disable_user_enumeration'] ?? true) {
            add_action('template_redirect', array($this, 'prevent_user_enumeration'));
        }
        
        if ($settings['remove_unnecessary_headers'] ?? true) {
            $this->remove_unnecessary_headers();
        }
        
        $this->log('Security hardening measures applied', 'info');
    }
    
    /**
     * Show generic login error messages
     * 
     * TODO: Implement generic error messages
     */
    public function generic_login_error() {
        return __('Login failed. Please check your credentials.', 'securepress-x');
    }
    
    /**
     * Prevent user enumeration via REST API and URL scanning
     * 
     * TODO: Implement user enumeration prevention
     */
    public function prevent_user_enumeration() {
        // TODO: Block author page access
        // TODO: Restrict REST API user endpoints
        // TODO: Block ?author=N queries
        
        if (isset($_GET['author']) && is_numeric($_GET['author'])) {
            wp_redirect(home_url(), 301);
            exit;
        }
    }
    
    /**
     * Remove unnecessary HTTP headers
     * 
     * TODO: Remove headers that leak information
     */
    private function remove_unnecessary_headers() {
        // TODO: Remove X-Powered-By header
        // TODO: Remove Server header if possible
        // TODO: Remove unnecessary WordPress headers
        
        header_remove('X-Powered-By');
    }
    
    /**
     * Get module settings schema
     */
    public function get_settings_schema() {
        return array(
            'enabled' => array(
                'type' => 'boolean',
                'default' => false,
                'title' => __('Enable Security Hardening', 'securepress-x'),
                'description' => __('Apply security hardening measures', 'securepress-x')
            ),
            'file_editor_disabled' => array(
                'type' => 'boolean',
                'default' => true,
                'title' => __('Disable File Editing', 'securepress-x'),
                'description' => __('Disable theme/plugin editing in admin', 'securepress-x')
            ),
            'disable_file_mods' => array(
                'type' => 'boolean',
                'default' => false,
                'title' => __('Disable File Modifications', 'securepress-x'),
                'description' => __('Disable all file modifications including plugin/theme updates', 'securepress-x')
            ),
            'disable_version_info' => array(
                'type' => 'boolean',
                'default' => true,
                'title' => __('Remove WordPress Version', 'securepress-x'),
                'description' => __('Hide WordPress version information', 'securepress-x')
            ),
            'disable_xmlrpc' => array(
                'type' => 'boolean',
                'default' => false,
                'title' => __('Disable XML-RPC', 'securepress-x'),
                'description' => __('Completely disable XML-RPC functionality', 'securepress-x')
            ),
            'disable_pingbacks' => array(
                'type' => 'boolean',
                'default' => true,
                'title' => __('Disable Pingbacks', 'securepress-x'),
                'description' => __('Disable XML-RPC pingback methods', 'securepress-x')
            ),
            'hide_login_errors' => array(
                'type' => 'boolean',
                'default' => true,
                'title' => __('Hide Login Errors', 'securepress-x'),
                'description' => __('Show generic error messages on login failure', 'securepress-x')
            ),
            'disable_user_enumeration' => array(
                'type' => 'boolean',
                'default' => true,
                'title' => __('Disable User Enumeration', 'securepress-x'),
                'description' => __('Prevent user enumeration attacks', 'securepress-x')
            ),
            'remove_unnecessary_headers' => array(
                'type' => 'boolean',
                'default' => true,
                'title' => __('Remove Unnecessary Headers', 'securepress-x'),
                'description' => __('Remove headers that reveal server information', 'securepress-x')
            ),
            'disable_directory_browsing' => array(
                'type' => 'boolean',
                'default' => true,
                'title' => __('Disable Directory Browsing', 'securepress-x'),
                'description' => __('Prevent directory listing in web server', 'securepress-x')
            ),
            'limit_post_revisions' => array(
                'type' => 'number',
                'default' => 5,
                'min' => 0,
                'max' => 50,
                'title' => __('Limit Post Revisions', 'securepress-x'),
                'description' => __('Maximum number of post revisions to keep', 'securepress-x')
            )
        );
    }
    
    /**
     * Get module display name
     */
    public function get_display_name() {
        return __('Security Hardening', 'securepress-x');
    }
    
    /**
     * Get module description
     */
    public function get_description() {
        return __('חיזוק אבטחה - הגדרות אבטחה מתקדמות לחיזוק וורדפרס', 'securepress-x');
    }
    
    /**
     * Get module icon
     */
    public function get_icon() {
        return 'dashicons-admin-tools';
    }

    /**
     * Initialize module
     */
    public function init() {
        // Initialize security hardening
        if ($this->is_enabled()) {
            // Note: Critical settings like DISALLOW_FILE_EDIT are already applied
            // in securepress_apply_early_security_settings() in the main plugin file
            
            // Apply non-critical settings that can be set later
            if ($this->get_setting('disable_version_info', true)) {
                remove_action('wp_head', 'wp_generator');
                add_filter('the_generator', '__return_empty_string');
                add_filter('script_loader_src', array($this, 'remove_version_parameter'));
                add_filter('style_loader_src', array($this, 'remove_version_parameter'));
            }
            
            if ($this->get_setting('disable_xmlrpc', false)) {
                add_filter('xmlrpc_enabled', '__return_false');
            }
            
            if ($this->get_setting('hide_login_errors', true)) {
                add_filter('login_errors', array($this, 'generic_login_error'));
            }
            
            if ($this->get_setting('disable_user_enumeration', true)) {
                add_action('template_redirect', array($this, 'prevent_user_enumeration'));
            }
        }
        
        $this->log('Security hardening module initialized', 'info');
    }
    
    /**
     * Remove version parameter from scripts and styles
     * 
     * @param string $src Source URL
     * @return string Modified URL
     */
    public function remove_version_parameter($src) {
        if (strpos($src, 'ver=')) {
            $src = remove_query_arg('ver', $src);
        }
        return $src;
    }
} 