<?php
/**
 * SecurePress X REST API Class
 * 
 * Handles REST API endpoints for the React frontend
 * 
 * @package SecurePress_X
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST API class
 */
class SecurePress_Rest_API {
    
    /**
     * Plugin instance
     */
    private $plugin;
    
    /**
     * API namespace
     */
    private $namespace = 'securepressx/v1';
    
    /**
     * Constructor
     */
    public function __construct($plugin) {
        $this->plugin = $plugin;
        
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register API routes
     */
    public function register_routes() {
        // Register namespace
        register_rest_route('securepressx/v1', '/dashboard', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_dashboard_data'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => array(
                'context' => array(
                    'description' => 'Scope under which the request is made; determines fields present in response.',
                    'type' => 'string',
                    'enum' => array('view', 'edit'),
                    'default' => 'view',
                    'required' => false,
                ),
            ),
        ));

        register_rest_route('securepressx/v1', '/settings', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_settings'),
                'permission_callback' => array($this, 'check_admin_permissions'),
                'args' => array(
                    'context' => array(
                        'description' => 'Scope under which the request is made; determines fields present in response.',
                        'type' => 'string',
                        'enum' => array('view', 'edit'),
                        'default' => 'view',
                        'required' => false,
                    ),
                ),
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'update_settings'),
                'permission_callback' => array($this, 'check_admin_permissions'),
                'args' => array(
                    'settings' => array(
                        'description' => 'Plugin settings to update.',
                        'type' => 'object',
                        'required' => true,
                    ),
                ),
            ),
        ));

        register_rest_route('securepressx/v1', '/modules', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_modules'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => array(
                'context' => array(
                    'description' => 'Scope under which the request is made; determines fields present in response.',
                    'type' => 'string',
                    'enum' => array('view', 'edit'),
                    'default' => 'view',
                    'required' => false,
                ),
            ),
        ));

        register_rest_route('securepressx/v1', '/modules/(?P<module_id>[a-zA-Z0-9_-]+)', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_module'),
                'permission_callback' => array($this, 'check_admin_permissions'),
                'args' => array(
                    'module_id' => array(
                        'description' => 'Unique identifier for the module.',
                        'type' => 'string',
                        'required' => true,
                    ),
                    'context' => array(
                        'description' => 'Scope under which the request is made; determines fields present in response.',
                        'type' => 'string',
                        'enum' => array('view', 'edit'),
                        'default' => 'view',
                        'required' => false,
                    ),
                ),
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'update_module'),
                'permission_callback' => array($this, 'check_admin_permissions'),
                'args' => array(
                    'module_id' => array(
                        'description' => 'Unique identifier for the module.',
                        'type' => 'string',
                        'required' => true,
                    ),
                    'settings' => array(
                        'description' => 'Module settings to update.',
                        'type' => 'object',
                        'required' => true,
                    ),
                ),
            ),
        ));

        register_rest_route('securepressx/v1', '/logs', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_logs'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => array(
                'page' => array(
                    'description' => 'Current page of the collection.',
                    'type' => 'integer',
                    'default' => 1,
                    'minimum' => 1,
                    'required' => false,
                ),
                'per_page' => array(
                    'description' => 'Maximum number of items to be returned in result set.',
                    'type' => 'integer',
                    'default' => 10,
                    'minimum' => 1,
                    'maximum' => 100,
                    'required' => false,
                ),
                'level' => array(
                    'description' => 'Filter logs by level.',
                    'type' => 'string',
                    'enum' => array('low', 'medium', 'high', 'critical'),
                    'required' => false,
                ),
                'search' => array(
                    'description' => 'Limit results to those matching a string.',
                    'type' => 'string',
                    'required' => false,
                ),
            ),
        ));

        register_rest_route('securepressx/v1', '/scan', array(
            'methods' => 'POST',
            'callback' => array($this, 'start_security_scan'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => array(
                'scan_type' => array(
                    'description' => 'Type of security scan to perform.',
                    'type' => 'string',
                    'enum' => array('full', 'quick', 'malware', 'integrity'),
                    'default' => 'full',
                    'required' => false,
                ),
            ),
        ));
    }
    
    /**
     * Check API permissions
     */
    public function check_permissions() {
        return current_user_can('manage_options');
    }
    
    /**
     * Get dashboard data
     */
    public function get_dashboard_data($request) {
        try {
            $data = array(
                'security_score' => $this->calculate_security_score(),
                'active_modules' => $this->get_active_modules_count(),
                'recent_events' => $this->get_recent_events(5),
                'threats_blocked' => $this->get_threats_blocked_count(),
                'last_scan' => $this->get_last_scan_info(),
                'quick_stats' => $this->get_quick_stats()
            );
            
            return rest_ensure_response($data);
        } catch (Exception $e) {
            return new WP_Error('api_error', $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * Get all modules
     */
    public function get_modules($request) {
        try {
            $modules = array();
            foreach ($this->plugin->get_modules() as $module_id => $module) {
                $modules[] = array(
                    'id' => $module_id,
                    'name' => $module->get_display_name(),
                    'description' => $module->get_description(),
                    'icon' => $module->get_icon(),
                    'enabled' => $module->is_enabled(),
                    'settings_schema' => $module->get_settings_schema(),
                    'current_settings' => $module->get_settings()
                );
            }
            
            return rest_ensure_response($modules);
        } catch (Exception $e) {
            return new WP_Error('api_error', $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * Get single module
     */
    public function get_module($request) {
        $module_id = $request->get_param('module_id');
        
        try {
            $module = $this->plugin->get_module($module_id);
            if (!$module) {
                return new WP_Error('module_not_found', __('Module not found', 'securepress-x'), array('status' => 404));
            }
            
            $data = array(
                'id' => $module_id,
                'name' => $module->get_display_name(),
                'description' => $module->get_description(),
                'icon' => $module->get_icon(),
                'enabled' => $module->is_enabled(),
                'settings_schema' => $module->get_settings_schema(),
                'current_settings' => $module->get_settings()
            );
            
            return rest_ensure_response($data);
        } catch (Exception $e) {
            return new WP_Error('api_error', $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * Update module settings
     */
    public function update_module($request) {
        $module_id = $request->get_param('module_id');
        $settings = $request->get_param('settings');
        
        try {
            $module = $this->plugin->get_module($module_id);
            if (!$module) {
                return new WP_Error('module_not_found', __('Module not found', 'securepress-x'), array('status' => 404));
            }
            
            // Validate settings against schema
            $schema = $module->get_settings_schema();
            $validated_settings = $this->validate_settings($settings, $schema);
            
            if (is_wp_error($validated_settings)) {
                return $validated_settings;
            }
            
            // Update settings
            $result = $module->update_settings($validated_settings);
            
            if ($result) {
                // Log the change
                $this->plugin->get_logger()->log(
                    'module_update',
                    "Module '$module_id' settings updated",
                    'info'
                );
                
                return rest_ensure_response(array(
                    'success' => true,
                    'message' => __('Settings updated successfully', 'securepress-x'),
                    'settings' => $module->get_settings()
                ));
            } else {
                return new WP_Error('update_failed', __('Failed to update settings', 'securepress-x'), array('status' => 500));
            }
        } catch (Exception $e) {
            return new WP_Error('api_error', $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * Get logs with filtering and pagination
     */
    public function get_logs($request) {
        $page = $request->get_param('page') ? (int) $request->get_param('page') : 1;
        $per_page = $request->get_param('per_page') ? (int) $request->get_param('per_page') : 20;
        $type = $request->get_param('type');
        $severity = $request->get_param('severity');
        $search = $request->get_param('search');
        $date_from = $request->get_param('date_from');
        $date_to = $request->get_param('date_to');
        
        try {
            $logger = $this->plugin->get_logger();
            $logs = $logger->get_logs(array(
                'page' => $page,
                'per_page' => $per_page,
                'type' => $type,
                'severity' => $severity,
                'search' => $search,
                'date_from' => $date_from,
                'date_to' => $date_to
            ));
            
            return rest_ensure_response($logs);
        } catch (Exception $e) {
            return new WP_Error('api_error', $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * Get plugin settings
     */
    public function get_settings($request) {
        try {
            $default_settings = array(
                'login_protection' => array(
                    'enabled' => false,
                    'custom_slug' => 'secure-login',
                    'redirect_404_to_home' => true,
                ),
                'http_headers' => array(
                    'enabled' => false,
                    'hsts_enabled' => true,
                    'xframe_enabled' => true,
                    'x_content_type_options' => true,
                    'x_xss_protection' => true,
                    'referrer_policy' => true,
                    'csp_enabled' => false,
                    'custom_csp_policy' => '',
                    'custom_ido_header_enabled' => false,
                    'custom_ido_header_value' => 'Secure',
                ),
                'file_integrity' => array(
                    'enabled' => false,
                    'scan_frequency' => 'daily',
                    'scan_core' => true,
                    'scan_plugins' => true,
                    'scan_themes' => true,
                    'notify_email' => false,
                    'notify_webhook' => false,
                    'webhook_url' => '',
                ),
                'brute_force' => array(
                    'enabled' => true,
                    'max_attempts' => 5,
                    'lockout_duration' => 1800,
                    'recaptcha_enabled' => false,
                    'recaptcha_site_key' => '',
                    'recaptcha_secret_key' => '',
                ),
                'api_access' => array(
                    'xmlrpc_enabled' => true,
                    'rest_api_enabled' => true,
                    'rest_api_restricted' => false,
                ),
                'two_factor' => array(
                    'enabled' => false,
                    'enforcement_roles' => array('administrator'),
                ),
                'auto_update' => array(
                    'enabled' => false,
                    'core_security' => true,
                    'plugin_security' => true,
                    'theme_security' => true,
                ),
                'hardening' => array(
                    'secure_all_enabled' => false,
                    'file_editor_disabled' => true,
                    'debug_disabled' => true,
                    'disable_user_enumeration' => true,
                    'disable_version_info' => true,
                ),
                'audit_log' => array(
                    'enabled' => true,
                    'retention_days' => 30,
                    'notifications_enabled' => false,
                    'notification_type' => 'email',
                    'notification_email' => '',
                    'notification_webhook_url' => '',
                    'log_level' => 'all',
                )
            );
            
            // Get settings with correct option name
            $settings = get_option('securepress_x_settings', array());
            
            // Ensure all default settings exist by merging with defaults
            $settings = wp_parse_args($settings, $default_settings);
            
            // Ensure all nested settings exist
            foreach ($default_settings as $section => $section_defaults) {
                if (!isset($settings[$section])) {
                    $settings[$section] = array();
                }
                $settings[$section] = wp_parse_args($settings[$section], $section_defaults);
            }
            
            return rest_ensure_response($settings);
        } catch (Exception $e) {
            return new WP_Error('api_error', $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * Update global settings
     */
    public function update_settings($request) {
        try {
            // Get settings from request - support both direct and nested format
            $data = $request->get_params();
            $settings = isset($data['settings']) ? $data['settings'] : $data;
            
            if (!is_array($settings)) {
                return new WP_Error('invalid_settings', __('Invalid settings data', 'securepress-x'), array('status' => 400));
            }
            
            // Debug log
            error_log('SecurePress X: Updating settings: ' . wp_json_encode($settings));
            
            try {
                // Validate settings against default structure
                $default_settings = $this->get_settings(new WP_REST_Request())->get_data();
                
                // Create a new settings array to ensure proper structure
                $validated_settings = array();
                
                // Ensure all required sections exist
                foreach ($default_settings as $section => $section_defaults) {
                    if (!isset($settings[$section]) || !is_array($settings[$section])) {
                        $validated_settings[$section] = $section_defaults;
                    } else {
                        // Ensure each section has all required keys with proper types
                        $validated_settings[$section] = array();
                        foreach ($section_defaults as $key => $default_value) {
                            if (isset($settings[$section][$key])) {
                                // Type casting based on default value type
                                $type = gettype($default_value);
                                switch ($type) {
                                    case 'boolean':
                                        $validated_settings[$section][$key] = (bool) $settings[$section][$key];
                                        break;
                                    case 'integer':
                                        $validated_settings[$section][$key] = (int) $settings[$section][$key];
                                        break;
                                    case 'string':
                                        $validated_settings[$section][$key] = is_string($settings[$section][$key]) ? 
                                            sanitize_text_field($settings[$section][$key]) : (string) $settings[$section][$key];
                                        break;
                                    case 'array':
                                        $validated_settings[$section][$key] = is_array($settings[$section][$key]) ? 
                                            $settings[$section][$key] : $default_value;
                                        break;
                                    default:
                                        $validated_settings[$section][$key] = $settings[$section][$key];
                                }
                            } else {
                                $validated_settings[$section][$key] = $default_value;
                            }
                        }
                    }
                }
                
                // Remove any extra settings that aren't in the default structure
                // We don't need to process these, just skip them
                
                // Update the option with validated settings
                $result = update_option('securepress_x_settings', $validated_settings);
                
                if ($result) {
                    // Log success
                    error_log('SecurePress X: Settings updated successfully');
                    
                    try {
                        $this->plugin->get_logger()->log(
                            'settings_update',
                            'Global settings updated',
                            'info'
                        );
                    } catch (Exception $log_error) {
                        error_log('SecurePress X: Error logging settings update: ' . $log_error->getMessage());
                        // Continue even if logging fails
                    }
                    
                    return rest_ensure_response(array(
                        'success' => true,
                        'message' => __('Settings updated successfully', 'securepress-x'),
                        'settings' => $validated_settings
                    ));
                } else {
                    // Log failure
                    error_log('SecurePress X: Failed to update settings - no changes or option already exists');
                    
                    // If no changes were made, still return success
                    return rest_ensure_response(array(
                        'success' => true,
                        'message' => __('No changes were made to settings', 'securepress-x'),
                        'settings' => $validated_settings
                    ));
                }
            } catch (Exception $validation_error) {
                error_log('SecurePress X: Validation error in update_settings: ' . $validation_error->getMessage());
                return new WP_Error('validation_error', $validation_error->getMessage(), array('status' => 400));
            }
        } catch (Exception $e) {
            error_log('SecurePress X: Exception in update_settings: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            return new WP_Error('api_error', $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * Start security scan
     */
    public function start_security_scan($request) {
        $scan_type = $request->get_param('scan_type');
        
        try {
            // TODO: Implement security scanning
            // For now, return a placeholder response
            
            $this->plugin->get_logger()->log(
                'security_scan',
                "Security scan initiated: $scan_type",
                'info'
            );
            
            return rest_ensure_response(array(
                'success' => true,
                'message' => __('Security scan started', 'securepress-x'),
                'scan_id' => wp_generate_uuid4(),
                'estimated_duration' => 300 // 5 minutes
            ));
        } catch (Exception $e) {
            return new WP_Error('api_error', $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * Get scan status
     */
    public function get_scan_status($request) {
        try {
            // TODO: Implement scan status checking
            // For now, return a placeholder response
            
            return rest_ensure_response(array(
                'status' => 'idle', // idle, running, completed, error
                'progress' => 0,
                'message' => __('No scan running', 'securepress-x'),
                'last_scan' => $this->get_last_scan_info()
            ));
        } catch (Exception $e) {
            return new WP_Error('api_error', $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * Helper methods
     */
    
    /**
     * Calculate security score
     */
    private function calculate_security_score() {
        // TODO: Implement security score calculation
        $score = 75; // Placeholder
        
        $modules = $this->plugin->get_modules();
        $enabled_count = 0;
        $total_count = count($modules);
        
        foreach ($modules as $module) {
            if ($module->is_enabled()) {
                $enabled_count++;
            }
        }
        
        $module_score = $total_count > 0 ? ($enabled_count / $total_count) * 100 : 0;
        
        return array(
            'overall' => min(100, max(0, $score)),
            'modules' => $module_score,
            'configuration' => 80, // Placeholder
            'last_updated' => time()
        );
    }
    
    /**
     * Get active modules count
     */
    private function get_active_modules_count() {
        return count($this->plugin->get_active_modules());
    }
    
    /**
     * Get recent events
     */
    private function get_recent_events($limit = 5) {
        $logger = $this->plugin->get_logger();
        return $logger->get_logs(array('per_page' => $limit, 'page' => 1));
    }
    
    /**
     * Get threats blocked count
     */
    private function get_threats_blocked_count() {
        // TODO: Implement threat counting
        return 42; // Placeholder
    }
    
    /**
     * Get last scan info
     */
    private function get_last_scan_info() {
        // TODO: Implement last scan info
        return array(
            'date' => null,
            'type' => null,
            'status' => 'never',
            'issues_found' => 0
        );
    }
    
    /**
     * Get quick stats
     */
    private function get_quick_stats() {
        return array(
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'ssl_enabled' => is_ssl(),
            'updates_available' => $this->check_updates_available()
        );
    }
    
    /**
     * Check if updates are available
     */
    private function check_updates_available() {
        $updates = array(
            'core' => false,
            'plugins' => 0,
            'themes' => 0
        );
        
        // Only check updates if in admin context or if functions are available
        if (!function_exists('get_core_updates')) {
            // Load admin functions if not already loaded
            if (is_admin()) {
                require_once ABSPATH . 'wp-admin/includes/update.php';
            } else {
                // Return default values if not in admin context
                return $updates;
            }
        }
        
        // Check core updates
        if (function_exists('get_core_updates')) {
            $core_updates = get_core_updates();
            if (!empty($core_updates) && $core_updates[0]->response !== 'latest') {
                $updates['core'] = true;
            }
        }
        
        // Check plugin updates
        if (function_exists('get_plugin_updates')) {
            $plugin_updates = get_plugin_updates();
            $updates['plugins'] = count($plugin_updates);
        }
        
        // Check theme updates
        if (function_exists('get_theme_updates')) {
            $theme_updates = get_theme_updates();
            $updates['themes'] = count($theme_updates);
        }
        
        return $updates;
    }
    
    /**
     * Validate settings against schema
     */
    private function validate_settings($settings, $schema) {
        $validated = array();
        
        foreach ($schema as $key => $field_schema) {
            $value = isset($settings[$key]) ? $settings[$key] : $field_schema['default'];
            
            // Type validation
            switch ($field_schema['type']) {
                case 'boolean':
                    $validated[$key] = (bool) $value;
                    break;
                case 'number':
                    $validated[$key] = (int) $value;
                    if (isset($field_schema['min']) && $validated[$key] < $field_schema['min']) {
                        $validated[$key] = $field_schema['min'];
                    }
                    if (isset($field_schema['max']) && $validated[$key] > $field_schema['max']) {
                        $validated[$key] = $field_schema['max'];
                    }
                    break;
                case 'email':
                    if (!is_email($value)) {
                        return new WP_Error('invalid_email', "Invalid email for field $key", array('status' => 400));
                    }
                    $validated[$key] = sanitize_email($value);
                    break;
                case 'url':
                    if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                        return new WP_Error('invalid_url', "Invalid URL for field $key", array('status' => 400));
                    }
                    $validated[$key] = esc_url_raw($value);
                    break;
                case 'textarea':
                case 'text':
                default:
                    $validated[$key] = sanitize_text_field($value);
                    break;
            }
        }
        
        return $validated;
    }

    public function check_admin_permissions() {
        return current_user_can('manage_options');
    }
} 