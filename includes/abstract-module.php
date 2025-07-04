<?php
/**
 * Abstract SecurePress Module Class
 * 
 * Base class that all security modules must extend.
 * Provides common functionality and enforces module structure.
 * 
 * @package SecurePress_X
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Abstract module class
 */
abstract class SecurePress_Module {
    
    /**
     * Module slug/name
     * 
     * @var string
     */
    protected $module_name;
    
    /**
     * Module settings
     * 
     * @var array
     */
    protected $settings = array();
    
    /**
     * Constructor
     * 
     * @param string $module_name Module name
     */
    public function __construct($module_name) {
        $this->module_name = $module_name;
        $this->load_settings();
        $this->register_hooks();
    }
    
    /**
     * Load module settings from database
     */
    protected function load_settings() {
        $all_settings = get_option('securepress_settings', array());
        $this->settings = isset($all_settings[$this->module_name]) ? $all_settings[$this->module_name] : array();
    }
    
    /**
     * Check if module is enabled
     * 
     * @return bool True if enabled, false otherwise
     */
    public function is_enabled() {
        return isset($this->settings['enabled']) ? (bool) $this->settings['enabled'] : false;
    }
    
    /**
     * Get module setting value
     * 
     * @param string $key Setting key
     * @param mixed $default Default value if setting doesn't exist
     * @return mixed Setting value or default
     */
    protected function get_setting($key, $default = null) {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }
    
    /**
     * Get all module settings
     * 
     * @return array Module settings
     */
    public function get_settings() {
        return $this->settings;
    }
    
    /**
     * Update a single module setting
     * 
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @return bool Success status
     */
    public function update_setting($key, $value) {
        $this->settings[$key] = $value;
        return $this->save_settings();
    }
    
    /**
     * Update multiple module settings
     * 
     * @param array $settings Settings to update
     * @return bool Success status
     */
    public function update_settings($settings) {
        $this->settings = array_merge($this->settings, $settings);
        return $this->save_settings();
    }
    
    /**
     * Save module settings to database
     * 
     * @return bool Success status
     */
    protected function save_settings() {
        $all_settings = get_option('securepress_settings', array());
        $all_settings[$this->module_name] = $this->settings;
        return update_option('securepress_settings', $all_settings);
    }
    
    /**
     * Get module name
     * 
     * @return string Module name
     */
    public function get_module_name() {
        return $this->module_name;
    }
    
    /**
     * Log module activity
     * 
     * @param string $message Log message
     * @param string $level Log level (info, warning, error)
     * @param array $context Additional context data
     */
    protected function log($message, $level = 'info', $context = array()) {
        if (class_exists('SecurePress_Logger')) {
            // Add module name to context
            $context['module'] = $this->module_name;
            
            // Create log type from context and message
            $type = !empty($context['type']) ? $context['type'] : 'module_event';
            
            // Convert context to JSON for storage
            $context_json = wp_json_encode($context);
            if ($context_json === false) {
                $context_json = '{"error": "Failed to encode context"}';
            }
            
            SecurePress_Logger::log($type, $message, $context_json);
        }
    }
    
    /**
     * Register WordPress hooks (abstract method)
     * 
     * Each module must implement this method to register its hooks
     */
    abstract protected function register_hooks();
    
    /**
     * Initialize module (abstract method)
     * 
     * Called when module is enabled and plugin initializes
     */
    abstract public function init();
    
    /**
     * Get module settings schema (abstract method)
     * 
     * Returns the settings structure for this module
     * 
     * @return array Settings schema
     */
    abstract public function get_settings_schema();
    
    /**
     * Get module display name (abstract method)
     * 
     * @return string Display name for admin interface
     */
    abstract public function get_display_name();
    
    /**
     * Get module description (abstract method)
     * 
     * @return string Module description for admin interface
     */
    abstract public function get_description();
    
    /**
     * Get module icon (abstract method)
     * 
     * @return string Icon class or SVG for admin interface
     */
    abstract public function get_icon();
    
    /**
     * Validate module settings
     * 
     * Override this method to add custom validation
     * 
     * @param array $settings Settings to validate
     * @return array Validated settings
     */
    public function validate_settings($settings) {
        // Basic validation - modules can override for custom validation
        $schema = $this->get_settings_schema();
        $validated = array();
        
        foreach ($schema as $key => $field) {
            if (isset($settings[$key])) {
                $value = $settings[$key];
                
                // Type casting based on field type
                switch ($field['type']) {
                    case 'boolean':
                        $validated[$key] = (bool) $value;
                        break;
                    case 'integer':
                        $validated[$key] = (int) $value;
                        break;
                    case 'string':
                        $validated[$key] = sanitize_text_field($value);
                        break;
                    case 'textarea':
                        $validated[$key] = sanitize_textarea_field($value);
                        break;
                    case 'url':
                        $validated[$key] = esc_url_raw($value);
                        break;
                    case 'email':
                        $validated[$key] = sanitize_email($value);
                        break;
                    default:
                        $validated[$key] = $value;
                }
            } else {
                // Use default value if not provided
                $validated[$key] = isset($field['default']) ? $field['default'] : '';
            }
        }
        
        return $validated;
    }
    
    /**
     * Get module status info
     * 
     * Returns status information for admin dashboard
     * 
     * @return array Status information
     */
    public function get_status() {
        return array(
            'enabled' => $this->is_enabled(),
            'name' => $this->get_display_name(),
            'description' => $this->get_description(),
            'settings_count' => count($this->settings),
            'last_updated' => get_option('securepress_' . $this->module_name . '_last_updated', ''),
        );
    }
} 