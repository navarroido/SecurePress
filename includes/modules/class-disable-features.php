<?php
/**
 * SecurePress X Disable Features Module
 * 
 * Disables unnecessary WordPress features for security
 * 
 * @package SecurePress_X
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Disable Features module class
 * 
 * TODO: Implement feature disabling functionality
 */
class SecurePress_Disable_Features extends SecurePress_Module {
    
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
        
        // TODO: Add hooks for disabling features
    }
    
    /**
     * Initialize module
     */
    public function init() {
        // TODO: Initialize feature disabling
        $this->log('Disable features module initialized', 'info');
    }
    
    /**
     * Get module settings schema
     */
    public function get_settings_schema() {
        return array(
            'enabled' => array(
                'type' => 'boolean',
                'default' => false,
                'title' => __('Enable Feature Disabling', 'securepress-x'),
                'description' => __('Disable unnecessary WordPress features', 'securepress-x')
            ),
            'disable_xml_rpc' => array(
                'type' => 'boolean',
                'default' => true,
                'title' => __('Disable XML-RPC', 'securepress-x'),
                'description' => __('Disable XML-RPC functionality', 'securepress-x')
            ),
            'disable_rest_api' => array(
                'type' => 'boolean',
                'default' => false,
                'title' => __('Disable REST API', 'securepress-x'),
                'description' => __('Disable REST API for unauthenticated users', 'securepress-x')
            ),
            'disable_file_editing' => array(
                'type' => 'boolean',
                'default' => true,
                'title' => __('Disable File Editing', 'securepress-x'),
                'description' => __('Disable theme and plugin file editing in admin', 'securepress-x')
            )
        );
    }
    
    /**
     * Get module display name
     */
    public function get_display_name() {
        return __('Disable Features', 'securepress-x');
    }
    
    /**
     * Get module description
     */
    public function get_description() {
        return __('השבתת תכונות מיותרות - XML-RPC, עריכת קבצים ועוד', 'securepress-x');
    }
    
    /**
     * Get module icon
     */
    public function get_icon() {
        return 'dashicons-dismiss';
    }
} 