<?php
/**
 * SecurePress X Hide Login Module
 * 
 * Replaces wp-login.php and wp-admin URLs with custom secure slugs
 * to prevent brute force attacks and unauthorized access attempts.
 * 
 * @package SecurePress_X
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hide Login module class
 */
class SecurePress_Hide_Login extends SecurePress_Module {
    
    /**
     * Current request URI
     * 
     * @var string
     */
    private $request_uri;
    
    /**
     * Custom login slug
     * 
     * @var string
     */
    private $login_slug;
    
    /**
     * Constructor
     */
    public function __construct($module_id) {
        parent::__construct($module_id);
        $this->request_uri = $_SERVER['REQUEST_URI'];
    }
    
    /**
     * Register WordPress hooks
     */
    protected function register_hooks() {
        if (!$this->is_enabled()) {
            return;
        }
        
        // Hook early to catch login requests
        add_action('init', array($this, 'init'), 1);
        
        // Modify login URL filters
        add_filter('login_url', array($this, 'login_url'), 10, 3);
        add_filter('logout_url', array($this, 'logout_url'), 10, 2);
        add_filter('lostpassword_url', array($this, 'lostpassword_url'), 10, 2);
        add_filter('registration_url', array($this, 'registration_url'));
        
        // Handle redirects
        add_action('wp_loaded', array($this, 'handle_redirects'));
        
        // Add rewrite rules
        add_action('init', array($this, 'add_rewrite_rules'));
        
        // Handle direct access to wp-login.php
        add_action('login_init', array($this, 'block_default_login'));
        
        // Log access attempts
        add_action('wp_login_failed', array($this, 'log_failed_login'));
        add_action('wp_login', array($this, 'log_successful_login'), 10, 2);
    }
    
    /**
     * Initialize module
     */
    public function init() {
        // Initialize login slug
        $this->login_slug = $this->get_setting('custom_slug');
        if (empty($this->login_slug)) {
            // Use a fallback if wp_generate_password is not available yet
            if (function_exists('wp_generate_password')) {
                $this->login_slug = 'secure-login-' . wp_generate_password(5, false);
            } else {
                // Fallback to a simple random string
                $this->login_slug = 'secure-login-' . substr(md5(uniqid(rand(), true)), 0, 5);
            }
            $this->update_setting('custom_slug', $this->login_slug);
        }

        // Check if this is our custom login slug
        if ($this->is_custom_login_request()) {
            $this->handle_custom_login();
        }
    }
    
    /**
     * Add rewrite rules for custom login
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^' . $this->login_slug . '/?$',
            'wp-login.php',
            'top'
        );
        
        add_rewrite_rule(
            '^' . $this->login_slug . '/(.*)$',
            'wp-login.php?$1',
            'top'
        );
    }
    
    /**
     * Handle custom login requests
     */
    private function handle_custom_login() {
        // Set global to indicate this is a valid login request
        define('SECUREPRESS_VALID_LOGIN', true);
        
        // Parse query string if present
        $query_string = '';
        if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) {
            $query_string = '?' . $_SERVER['QUERY_STRING'];
        }
        
        // Include WordPress login file
        require_once ABSPATH . 'wp-login.php';
        exit;
    }
    
    /**
     * Check if current request is for custom login
     * 
     * @return bool True if custom login request
     */
    private function is_custom_login_request() {
        $path = trim(parse_url($this->request_uri, PHP_URL_PATH), '/');
        $login_path = trim($this->login_slug, '/');
        
        return $path === $login_path || strpos($path, $login_path . '/') === 0;
    }
    
    /**
     * Block access to default wp-login.php
     */
    public function block_default_login() {
        $request_uri = $_SERVER['REQUEST_URI'];
        
        // Check if this is a request to wp-login.php
        if (strpos($request_uri, 'wp-login.php') !== false) {
            // Log the attempt
            SecurePress_Logger::log(
                'blocked_login_access',
                'Blocked access to default wp-login.php',
                'medium'
            );
            
            if ($this->get_setting('redirect_404_to_home', true)) {
                wp_redirect(home_url(), 302);
            } else {
                status_header(404);
                include get_404_template();
            }
            
            exit;
        }
    }
    
    /**
     * Handle redirects for blocked access
     */
    public function handle_redirects() {
        $request_uri = $_SERVER['REQUEST_URI'];
        
        // Block direct access to wp-admin without proper authentication
        if (strpos($request_uri, '/wp-admin') !== false && 
            !is_user_logged_in() && 
            !$this->is_ajax_or_api_request()) {
            
            // Log the attempt
            SecurePress_Logger::log(
                'blocked_admin_access',
                'Blocked unauthenticated access to wp-admin',
                'medium'
            );
            
            if ($this->get_setting('redirect_404_to_home', true)) {
                wp_redirect(home_url(), 302);
            } else {
                status_header(404);
                include get_404_template();
            }
            
            exit;
        }
    }
    
    /**
     * Filter login URL
     * 
     * @param string $login_url Login URL
     * @param string $redirect Redirect URL
     * @param bool $force_reauth Force reauth
     * @return string Modified login URL
     */
    public function login_url($login_url, $redirect = '', $force_reauth = false) {
        $custom_url = home_url($this->login_slug);
        
        $args = array();
        
        if (!empty($redirect)) {
            $args['redirect_to'] = urlencode($redirect);
        }
        
        if ($force_reauth) {
            $args['reauth'] = '1';
        }
        
        if (!empty($args)) {
            $custom_url = add_query_arg($args, $custom_url);
        }
        
        return $custom_url;
    }
    
    /**
     * Filter logout URL
     * 
     * @param string $logout_url Logout URL
     * @param string $redirect Redirect URL
     * @return string Modified logout URL
     */
    public function logout_url($logout_url, $redirect = '') {
        $args = array('action' => 'logout');
        
        if (!empty($redirect)) {
            $args['redirect_to'] = urlencode($redirect);
        }
        
        $args['_wpnonce'] = wp_create_nonce('log-out');
        
        return add_query_arg($args, home_url($this->login_slug));
    }
    
    /**
     * Filter lost password URL
     * 
     * @param string $lostpassword_url Lost password URL
     * @param string $redirect Redirect URL
     * @return string Modified lost password URL
     */
    public function lostpassword_url($lostpassword_url, $redirect = '') {
        $args = array('action' => 'lostpassword');
        
        if (!empty($redirect)) {
            $args['redirect_to'] = urlencode($redirect);
        }
        
        return add_query_arg($args, home_url($this->login_slug));
    }
    
    /**
     * Filter registration URL
     * 
     * @param string $register_url Registration URL
     * @return string Modified registration URL
     */
    public function registration_url($register_url) {
        return add_query_arg('action', 'register', home_url($this->login_slug));
    }
    
    /**
     * Log failed login attempt
     * 
     * @param string $username Username
     */
    public function log_failed_login($username) {
        SecurePress_Logger::log(
            'failed_login',
            "Failed login attempt for user: {$username}",
            'medium'
        );
    }
    
    /**
     * Log successful login
     * 
     * @param string $user_login Username
     * @param WP_User $user User object
     */
    public function log_successful_login($user_login, $user) {
        SecurePress_Logger::log(
            'successful_login',
            "Successful login for user: {$user_login}",
            'low'
        );
    }
    
    /**
     * Check if request is internal (AJAX, API, etc.)
     * 
     * @return bool True if internal request
     */
    private function is_internal_request() {
        return (
            defined('DOING_AJAX') && DOING_AJAX ||
            defined('REST_REQUEST') && REST_REQUEST ||
            defined('DOING_CRON') && DOING_CRON ||
            (isset($_GET['action']) && $_GET['action'] === 'heartbeat')
        );
    }
    
    /**
     * Check if request is AJAX or API
     * 
     * @return bool True if AJAX or API request
     */
    private function is_ajax_or_api_request() {
        return (
            wp_doing_ajax() ||
            (defined('REST_REQUEST') && REST_REQUEST) ||
            strpos($_SERVER['REQUEST_URI'], '/wp-json/') !== false
        );
    }
    
    /**
     * Get module settings schema
     * 
     * @return array Settings schema
     */
    public function get_settings_schema() {
        return array(
            'enabled' => array(
                'type' => 'boolean',
                'default' => false,
                'title' => __('Enable Hide Login', 'securepress-x'),
                'description' => __('Replace wp-login.php with a custom secure URL', 'securepress-x')
            ),
            'custom_slug' => array(
                'type' => 'string',
                'default' => function_exists('wp_generate_password') ? 
                    'secure-login-' . wp_generate_password(5, false) : 
                    'secure-login-' . substr(md5(uniqid(rand(), true)), 0, 5),
                'title' => __('Custom Login Slug', 'securepress-x'),
                'description' => __('Custom URL slug for login page (without slashes)', 'securepress-x'),
                'validation' => 'alphanumeric_dash'
            ),
            'redirect_404_to_home' => array(
                'type' => 'boolean',
                'default' => true,
                'title' => __('Redirect 404 to Home', 'securepress-x'),
                'description' => __('Redirect blocked login attempts to homepage instead of showing 404', 'securepress-x')
            )
        );
    }
    
    /**
     * Get module display name
     * 
     * @return string Display name
     */
    public function get_display_name() {
        return __('Hide Login URL', 'securepress-x');
    }
    
    /**
     * Get module description
     * 
     * @return string Module description
     */
    public function get_description() {
        return __('הסתר את כתובת הכניסה הסטנדרטית (/wp-login.php) והחלף אותה בכתובת מותאמת אישית למניעת התקפות Brute Force', 'securepress-x');
    }
    
    /**
     * Get module icon
     * 
     * @return string Icon class
     */
    public function get_icon() {
        return 'dashicons-hidden';
    }
    
    /**
     * Validate module settings
     * 
     * @param array $settings Settings to validate
     * @return array Validated settings
     */
    public function validate_settings($settings) {
        $validated = parent::validate_settings($settings);
        
        // Validate custom slug
        if (!empty($validated['custom_slug'])) {
            $slug = sanitize_title($validated['custom_slug']);
            
            // Ensure slug is not empty and doesn't conflict with WordPress
            $reserved_slugs = array(
                'wp-admin', 'wp-login', 'wp-content', 'wp-includes',
                'admin', 'login', 'register', 'dashboard', 'xmlrpc'
            );
            
            if (empty($slug) || in_array($slug, $reserved_slugs)) {
                if (function_exists('wp_generate_password')) {
                    $slug = 'secure-login-' . wp_generate_password(5, false);
                } else {
                    $slug = 'secure-login-' . substr(md5(uniqid(rand(), true)), 0, 5);
                }
            }
            
            $validated['custom_slug'] = $slug;
        }
        
        return $validated;
    }
    
    /**
     * Get current login URL
     * 
     * @return string Current custom login URL
     */
    public function get_current_login_url() {
        return home_url($this->login_slug);
    }
} 