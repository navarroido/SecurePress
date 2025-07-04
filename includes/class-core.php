<?php
/**
 * SecurePress X Core Class
 * 
 * Main plugin class that handles initialization, module loading,
 * and coordination between different security modules.
 * 
 * @package SecurePress_X
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main SecurePress Core Class
 */
class SecurePress_Core {
    
    /**
     * Single instance of the class
     * 
     * @var SecurePress_Core
     */
    private static $instance = null;
    
    /**
     * Plugin modules
     * 
     * @var array
     */
    private $modules = array();
    
    /**
     * Plugin settings
     * 
     * @var array
     */
    private $settings = array();
    
    /**
     * Logger instance
     * 
     * @var SecurePress_Logger
     */
    private $logger = null;
    
    /**
     * Get singleton instance
     * 
     * @return SecurePress_Core
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
        $this->load_modules();
        $this->init_admin();
    }
    
    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        // Load abstract module class and utilities first
        require_once SECUREPRESS_PLUGIN_DIR . 'includes/abstract-module.php';
        require_once SECUREPRESS_PLUGIN_DIR . 'includes/class-utilities.php';
        require_once SECUREPRESS_PLUGIN_DIR . 'includes/class-logger.php';
        require_once SECUREPRESS_PLUGIN_DIR . 'includes/class-installer.php';
        
        // Load REST API class for all requests
        require_once SECUREPRESS_PLUGIN_DIR . 'includes/admin/class-rest-api.php';
        
        // Load admin classes
        if (is_admin()) {
            require_once SECUREPRESS_PLUGIN_DIR . 'includes/admin/class-admin.php';
        }
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Load text domain
        add_action('init', array($this, 'load_textdomain'));
        
        // Load plugin on init
        add_action('init', array($this, 'init'));
        
        // Plugin action links
        add_filter('plugin_action_links_' . SECUREPRESS_PLUGIN_BASENAME, array($this, 'add_action_links'));
        
        // Add cron intervals
        add_filter('cron_schedules', array($this, 'add_cron_intervals'));
        
        // Handle cron jobs
        add_action('securepress_file_integrity_check', array($this, 'run_file_integrity_check'));
    }
    
    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'securepress-x',
            false,
            dirname(SECUREPRESS_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load settings
        $this->settings = get_option('securepress_settings', array());
        
        // Initialize modules
        foreach ($this->modules as $module) {
            if ($module instanceof SecurePress_Module && $module->is_enabled()) {
                $module->init();
            }
        }
        
        // Fire action after init
        do_action('securepress_init', $this);
    }
    
    /**
     * Load security modules
     */
    private function load_modules() {
        $module_files = array(
            'hide_login' => 'class-hide-login.php',
            'security_headers' => 'class-security-headers.php',
            'file_integrity' => 'class-file-integrity.php',
            'bruteforce_protection' => 'class-bruteforce-protection.php',
            'two_factor_auth' => 'class-two-factor-auth.php',
            'auto_patcher' => 'class-auto-patcher.php',
            'security_hardening' => 'class-security-hardening.php',
            'disable_features' => 'class-disable-features.php',
            'audit_log' => 'class-audit-log.php'
        );

        $module_classes = array(
            'hide_login' => 'SecurePress_Hide_Login',
            'security_headers' => 'SecurePress_Security_Headers',
            'file_integrity' => 'SecurePress_File_Integrity',
            'bruteforce_protection' => 'SecurePress_Bruteforce_Protection',
            'two_factor_auth' => 'SecurePress_Two_Factor_Auth',
            'auto_patcher' => 'SecurePress_Auto_Patcher',
            'security_hardening' => 'SecurePress_Security_Hardening',
            'disable_features' => 'SecurePress_Disable_Features',
            'audit_log' => 'SecurePress_Audit_Log'
        );

        foreach ($module_files as $module_id => $file) {
            $file_path = SECUREPRESS_PLUGIN_DIR . 'includes/modules/' . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
                
                $class_name = $module_classes[$module_id];
                if (class_exists($class_name)) {
                    try {
                        $this->modules[$module_id] = new $class_name($module_id);
                    } catch (Exception $e) {
                        // Log error but continue loading other modules
                        error_log("SecurePress X: Failed to load module $module_id - " . $e->getMessage());
                    }
                }
            }
        }
        
        // Allow modules to be filtered
        $this->modules = apply_filters('securepress_modules', $this->modules);
    }
    
    /**
     * Initialize admin functionality
     */
    private function init_admin() {
        // Always initialize REST API for all requests
        new SecurePress_Rest_API($this);
        
        // Initialize admin only in admin context
        if (is_admin()) {
            new SecurePress_Admin($this);
        }
    }
    
    /**
     * Add plugin action links
     * 
     * @param array $links Existing action links
     * @return array Modified action links
     */
    public function add_action_links($links) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=securepress-x-settings'),
            __('Settings', 'securepress-x')
        );
        
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Add custom cron intervals
     * 
     * @param array $schedules Existing cron schedules
     * @return array Modified schedules
     */
    public function add_cron_intervals($schedules) {
        $schedules['securepress_hourly'] = array(
            'interval' => HOUR_IN_SECONDS,
            'display' => __('SecurePress Hourly', 'securepress-x'),
        );
        
        $schedules['securepress_weekly'] = array(
            'interval' => WEEK_IN_SECONDS,
            'display' => __('SecurePress Weekly', 'securepress-x'),
        );
        
        return $schedules;
    }
    
    /**
     * Run file integrity check via cron
     */
    public function run_file_integrity_check() {
        if (isset($this->modules['file_integrity'])) {
            $this->modules['file_integrity']->run_integrity_check();
        }
    }
    
    /**
     * Get plugin version
     * 
     * @return string Plugin version
     */
    public function get_version() {
        return SECUREPRESS_VERSION;
    }
    
    /**
     * Get plugin settings
     * 
     * @param string $key Optional settings key
     * @return mixed Settings value or entire settings array
     */
    public function get_settings($key = null) {
        if ($key) {
            return isset($this->settings[$key]) ? $this->settings[$key] : array();
        }
        return $this->settings;
    }
    
    /**
     * Update plugin settings
     * 
     * @param array $new_settings New settings to save
     * @return bool Success status
     */
    public function update_settings($new_settings) {
        $this->settings = $new_settings;
        return update_option('securepress_settings', $new_settings);
    }
    
    /**
     * Get specific module
     * 
     * @param string $module_name Module name
     * @return SecurePress_Module|null Module instance or null
     */
    public function get_module($module_name) {
        return isset($this->modules[$module_name]) ? $this->modules[$module_name] : null;
    }
    
    /**
     * Get all modules
     * 
     * @return array All loaded modules
     */
    public function get_modules() {
        return $this->modules;
    }
    
    /**
     * Get logger instance
     * 
     * @return SecurePress_Logger Logger instance
     */
    public function get_logger() {
        if (!isset($this->logger)) {
            $this->logger = new SecurePress_Logger();
        }
        return $this->logger;
    }
    
    /**
     * Get active modules
     * 
     * @return array Active modules
     */
    public function get_active_modules() {
        return array_filter($this->modules, function($module) {
            return $module instanceof SecurePress_Module && $module->is_enabled();
        });
    }
} 