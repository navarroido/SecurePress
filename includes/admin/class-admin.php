<?php
/**
 * SecurePress X Admin Class
 * 
 * Handles WordPress admin integration, menu creation, and React interface loading
 * 
 * @package SecurePress_X
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin class
 */
class SecurePress_Admin {
    
    /**
     * The single instance of the class
     *
     * @var SecurePress_Admin
     */
    protected static $instance = null;
    
    /**
     * Main SecurePress_Admin Instance
     *
     * @return SecurePress_Admin
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Plugin instance
     */
    private $plugin;
    
    /**
     * Constructor
     */
    public function __construct($plugin) {
        $this->plugin = $plugin;
        add_action('admin_menu', array($this, 'add_menu_pages'));
        $this->init();
    }
    
    /**
     * Initialize admin functionality
     */
    private function init() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_securepress_x_api', array($this, 'handle_ajax_request'));
        add_action('admin_init', array($this, 'admin_init'));
    }
    
    /**
     * Admin initialization
     */
    public function admin_init() {
        // Register settings for WordPress Settings API (fallback)
        register_setting('securepress_x_settings', 'securepress_x_settings');
    }
    
    /**
     * Add menu items
     */
    public function add_menu_pages() {
        add_menu_page(
            __('SecurePress X', 'securepress-x'),
            __('SecurePress X', 'securepress-x'),
            'manage_options',
            'securepress-x',
            array($this, 'render_admin_page'),
            'dashicons-shield',
            30
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     * 
     * @param string $hook The current admin page hook
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'securepress-x') === false) {
            return;
        }

        // Get plugin info
        $plugin_url = plugin_dir_url(dirname(dirname(__FILE__)));
        $version = $this->plugin->get_version();

        // Enqueue admin styles and scripts
        wp_enqueue_style(
            'securepress-x-admin',
            $plugin_url . 'assets/dist/admin.css',
            array(),
            $version
        );

        wp_enqueue_script(
            'securepress-x-admin',
            $plugin_url . 'assets/dist/admin.js',
            array('wp-api-fetch', 'wp-element', 'wp-components'),
            $version,
            true
        );

        // Add nonce and API URL to script
        wp_localize_script(
            'securepress-x-admin',
            'securePressx',
            array(
                'nonce' => wp_create_nonce('wp_rest'),
                'apiUrl' => rest_url(),
                'version' => $version,
            )
        );
    }
    
    /**
     * Render React app container
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <div id="securepress-admin-app">
                <div class="loading-message">
                    <?php _e('Loading SecurePress X...', 'securepress-x'); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle AJAX requests (fallback for REST API)
     */
    public function handle_ajax_request() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'securepress_x_nonce')) {
            wp_die(__('Security check failed', 'securepress-x'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'securepress-x'));
        }
        
        $action = sanitize_text_field($_POST['action_type'] ?? '');
        
        switch ($action) {
            case 'get_dashboard_data':
                $this->ajax_get_dashboard_data();
                break;
            case 'get_modules':
                $this->ajax_get_modules();
                break;
            case 'update_module_settings':
                $this->ajax_update_module_settings();
                break;
            case 'get_logs':
                $this->ajax_get_logs();
                break;
            default:
                wp_send_json_error(__('Invalid action', 'securepress-x'));
        }
    }
    
    /**
     * AJAX: Get dashboard data
     */
    private function ajax_get_dashboard_data() {
        // TODO: Implement dashboard data collection
        wp_send_json_success(array(
            'security_status' => 'good',
            'active_modules' => count($this->plugin->get_active_modules()),
            'recent_events' => array()
        ));
    }
    
    /**
     * AJAX: Get modules data
     */
    private function ajax_get_modules() {
        $modules = array();
        foreach ($this->plugin->get_modules() as $module_id => $module) {
            $modules[] = array(
                'id' => $module_id,
                'name' => $module->get_display_name(),
                'description' => $module->get_description(),
                'icon' => $module->get_icon(),
                'enabled' => $module->is_enabled(),
                'settings' => $module->get_settings()
            );
        }
        
        wp_send_json_success($modules);
    }
    
    /**
     * AJAX: Update module settings
     */
    private function ajax_update_module_settings() {
        $module_id = sanitize_text_field($_POST['module_id'] ?? '');
        $settings = $_POST['settings'] ?? array();
        
        if (empty($module_id)) {
            wp_send_json_error(__('Module ID required', 'securepress-x'));
        }
        
        $module = $this->plugin->get_module($module_id);
        if (!$module) {
            wp_send_json_error(__('Module not found', 'securepress-x'));
        }
        
        // TODO: Validate and save settings
        $module->update_settings($settings);
        
        wp_send_json_success(__('Settings updated', 'securepress-x'));
    }
    
    /**
     * AJAX: Get logs
     */
    private function ajax_get_logs() {
        // TODO: Implement log retrieval
        wp_send_json_success(array());
    }
} 