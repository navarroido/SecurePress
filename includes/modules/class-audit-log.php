<?php
/**
 * SecurePress X Audit Log Module
 * 
 * Advanced audit logging and monitoring
 * 
 * @package SecurePress_X
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Audit Log module class
 */
class SecurePress_Audit_Log extends SecurePress_Module {
    
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
        
        // Log user login and logout events
        if ($this->get_setting('log_logins', true)) {
            add_action('wp_login', array($this, 'log_user_login'), 10, 2);
            add_action('wp_logout', array($this, 'log_user_logout'));
            add_action('wp_login_failed', array($this, 'log_failed_login'));
        }
        
        // Log admin actions
        if ($this->get_setting('log_admin_actions', true)) {
            add_action('updated_option', array($this, 'log_option_update'), 10, 3);
            add_action('activated_plugin', array($this, 'log_plugin_activation'));
            add_action('deactivated_plugin', array($this, 'log_plugin_deactivation'));
            add_action('switch_theme', array($this, 'log_theme_switch'), 10, 3);
            add_action('user_register', array($this, 'log_user_registration'));
            add_action('delete_user', array($this, 'log_user_deletion'));
            add_action('profile_update', array($this, 'log_profile_update'));
        }
    }
    
    /**
     * Initialize module
     */
    public function init() {
        global $wpdb;
        
        // Check if log table exists
        $table_name = $wpdb->prefix . 'securepress_log';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
        
        if (!$table_exists) {
            // Log error but don't create table here - it should be created during plugin activation
            error_log('SecurePress X: Audit log table does not exist. Please deactivate and reactivate the plugin.');
        }
        
        //$this->log('Audit log module initialized', 'info');
    }
    
    /**
     * Log user login
     */
    public function log_user_login($user_login, $user) {
        $this->log('login_success', sprintf(__('User %s logged in', 'securepress-x'), $user_login), 'info');
    }
    
    /**
     * Log user logout
     */
    public function log_user_logout() {
        $user = wp_get_current_user();
        if ($user->exists()) {
            $this->log('logout', sprintf(__('User %s logged out', 'securepress-x'), $user->user_login), 'info');
        }
    }
    
    /**
     * Log failed login
     */
    public function log_failed_login($username) {
        $this->log('login_failed', sprintf(__('Failed login attempt for user %s', 'securepress-x'), $username), 'warning');
    }
    
    /**
     * Log option update
     */
    public function log_option_update($option_name, $old_value, $new_value) {
        // Skip non-security related options and transients
        if (strpos($option_name, '_transient_') === 0 || strpos($option_name, '_site_transient_') === 0) {
            return;
        }
        
        // Focus on security-related options
        $security_options = array(
            'securepress_settings',
            'users_can_register',
            'admin_email',
            'siteurl',
            'home',
            'blog_public',
            'default_role',
            'WPLANG',
            'permalink_structure'
        );
        
        if (in_array($option_name, $security_options) || strpos($option_name, 'securepress') === 0) {
            $this->log('option_update', sprintf(__('Option %s was updated', 'securepress-x'), $option_name), 'info');
        }
    }
    
    /**
     * Log plugin activation
     */
    public function log_plugin_activation($plugin) {
        $this->log('plugin_activation', sprintf(__('Plugin %s was activated', 'securepress-x'), $plugin), 'info');
    }
    
    /**
     * Log plugin deactivation
     */
    public function log_plugin_deactivation($plugin) {
        $this->log('plugin_deactivation', sprintf(__('Plugin %s was deactivated', 'securepress-x'), $plugin), 'info');
    }
    
    /**
     * Log theme switch
     */
    public function log_theme_switch($new_name, $new_theme, $old_theme) {
        $this->log('theme_switch', sprintf(__('Theme changed from %s to %s', 'securepress-x'), $old_theme->get('Name'), $new_name), 'info');
    }
    
    /**
     * Log user registration
     */
    public function log_user_registration($user_id) {
        $user = get_userdata($user_id);
        if ($user) {
            $this->log('user_registration', sprintf(__('New user registered: %s', 'securepress-x'), $user->user_login), 'info');
        }
    }
    
    /**
     * Log user deletion
     */
    public function log_user_deletion($user_id) {
        $user = get_userdata($user_id);
        if ($user) {
            $this->log('user_deletion', sprintf(__('User deleted: %s', 'securepress-x'), $user->user_login), 'warning');
        }
    }
    
    /**
     * Log profile update
     */
    public function log_profile_update($user_id) {
        $user = get_userdata($user_id);
        if ($user) {
            $this->log('profile_update', sprintf(__('User profile updated: %s', 'securepress-x'), $user->user_login), 'info');
        }
    }
    
    /**
     * Get module settings schema
     */
    public function get_settings_schema() {
        return array(
            'enabled' => array(
                'type' => 'boolean',
                'default' => false,
                'title' => __('Enable Audit Log', 'securepress-x'),
                'description' => __('Advanced security event logging and monitoring', 'securepress-x')
            ),
            'retention_days' => array(
                'type' => 'number',
                'default' => 30,
                'min' => 1,
                'max' => 365,
                'title' => __('Log Retention (days)', 'securepress-x'),
                'description' => __('How long to keep log entries', 'securepress-x')
            ),
            'log_logins' => array(
                'type' => 'boolean',
                'default' => true,
                'title' => __('Log Login Events', 'securepress-x'),
                'description' => __('Log user login and logout events', 'securepress-x')
            ),
            'log_admin_actions' => array(
                'type' => 'boolean',
                'default' => true,
                'title' => __('Log Admin Actions', 'securepress-x'),
                'description' => __('Log administrative actions', 'securepress-x')
            ),
            'notifications_enabled' => array(
                'type' => 'boolean',
                'default' => false,
                'title' => __('Enable Notifications', 'securepress-x'),
                'description' => __('Send notifications for important security events', 'securepress-x')
            ),
            'notification_type' => array(
                'type' => 'select',
                'default' => 'email',
                'options' => array(
                    'email' => __('Email', 'securepress-x'),
                    'slack' => __('Slack', 'securepress-x'),
                    'webhook' => __('Webhook', 'securepress-x')
                ),
                'title' => __('Notification Method', 'securepress-x'),
                'description' => __('How to send security alerts', 'securepress-x')
            ),
            'notification_email' => array(
                'type' => 'email',
                'default' => '',
                'title' => __('Email Address', 'securepress-x'),
                'description' => __('Email address for security alerts', 'securepress-x')
            ),
            'log_level' => array(
                'type' => 'select',
                'default' => 'all',
                'options' => array(
                    'all' => __('All Events', 'securepress-x'),
                    'warnings' => __('Warnings & Errors', 'securepress-x'),
                    'errors' => __('Errors Only', 'securepress-x')
                ),
                'title' => __('Log Level', 'securepress-x'),
                'description' => __('Which events to log', 'securepress-x')
            )
        );
    }
    
    /**
     * Get module display name
     */
    public function get_display_name() {
        return __('Audit Log', 'securepress-x');
    }
    
    /**
     * Get module description
     */
    public function get_description() {
        return __('רישום ביקורת מתקדם - מעקב אחר פעילות ואירועי אבטחה', 'securepress-x');
    }
    
    /**
     * Get module icon
     */
    public function get_icon() {
        return 'dashicons-list-view';
    }
} 