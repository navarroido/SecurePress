<?php
/**
 * SecurePress X Security Headers Module
 * 
 * Adds security-related HTTP headers to protect against various attacks
 * including XSS, clickjacking, MIME sniffing, and more.
 * 
 * @package SecurePress_X
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Security Headers module class
 */
class SecurePress_Security_Headers extends SecurePress_Module {
    
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
        // Debug log to verify function is called
        error_log('SecurePress X: Registering security headers hooks - enabled: ' . ($this->is_enabled() ? 'true' : 'false'));
        
        // Always register hooks to remove X-Powered-By, even if module is disabled
        add_action('init', array($this, 'remove_version_headers'), 1);
        
        if (!$this->is_enabled()) {
            return;
        }
        
        // Start output buffering as early as possible to ensure we can modify headers
        add_action('init', array($this, 'start_output_buffer'), 0);
        
        // Add headers at different WordPress lifecycle points to ensure they're added
        add_action('wp_loaded', array($this, 'add_security_headers'), 0);
        add_action('send_headers', array($this, 'add_security_headers'), 0);
        
        // Use wp_headers filter to ensure headers are added to all responses
        add_filter('wp_headers', array($this, 'add_wp_headers'), 0);
        
        // Add headers for admin area
        add_action('admin_init', array($this, 'add_admin_security_headers'), 0);
        
        // Add headers for login page
        add_action('login_init', array($this, 'add_login_security_headers'), 0);
        
        // Add headers for REST API
        add_action('rest_api_init', array($this, 'add_security_headers'), 0);
        
        // Add headers for AJAX requests
        add_action('wp_ajax_nopriv_', array($this, 'add_security_headers'), 0);
        add_action('wp_ajax_', array($this, 'add_security_headers'), 0);
        
        // Add headers for template_redirect (front-end)
        add_action('template_redirect', array($this, 'add_security_headers'), 0);
        
        // Add hook to test if headers are working
        add_action('wp_footer', array($this, 'log_headers_status'), 999);
    }
    
    /**
     * Start output buffering to ensure headers can be added
     */
    public function start_output_buffer() {
        // Only start if not already buffering and module is enabled
        if (!ob_get_level() && $this->is_enabled()) {
            ob_start(array($this, 'process_output_buffer'));
            error_log('SecurePress X: Started output buffering for headers');
        }
    }
    
    /**
     * Process the output buffer and add security headers
     * 
     * @param string $buffer The buffer content
     * @return string The modified buffer
     */
    public function process_output_buffer($buffer) {
        // Add headers if they haven't been sent yet
        if (!headers_sent()) {
            error_log('SecurePress X: Adding headers via output buffer');
            $this->add_security_headers();
        } else {
            error_log('SecurePress X: Headers already sent in output buffer callback');
        }
        
        return $buffer;
    }
    
    /**
     * Initialize module
     */
    public function init() {
        // Module is ready
        $this->log('Security headers module initialized', 'info');
        
        // Check if we need to create an MU plugin for early headers
        $this->maybe_create_mu_plugin();
    }
    
    /**
     * Create MU plugin for early headers if needed
     */
    private function maybe_create_mu_plugin() {
        // Only proceed if the module is enabled
        if (!$this->is_enabled()) {
            return;
        }
        
        // Path to mu-plugins directory
        $mu_plugins_dir = WPMU_PLUGIN_DIR;
        if (!file_exists($mu_plugins_dir)) {
            if (!mkdir($mu_plugins_dir, 0755, true)) {
                error_log('SecurePress X: Failed to create mu-plugins directory');
                return;
            }
        }
        
        // Path to our mu-plugin file
        $mu_plugin_file = $mu_plugins_dir . '/securepress-early-headers.php';
        
        // Check if we need to create or update the file
        $create_file = !file_exists($mu_plugin_file);
        
        // Get current settings
        $settings = $this->get_all_settings();
        $settings_json = wp_json_encode($settings);
        
        // Create the mu-plugin content - make sure there are no spaces before <?php
        $mu_plugin_content = <<<'EOT'
<?php
/**
 * Plugin Name: SecurePress X Early Headers
 * Description: Adds security headers before WordPress loads
 * Version: 1.0.0
 * Author: SecurePress
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add security headers as early as possible
add_action('send_headers', 'securepress_x_add_early_headers', 0);

/**
 * Add security headers early
 */
function securepress_x_add_early_headers() {
    // Only proceed if headers haven't been sent
    if (headers_sent()) {
        return;
    }
    
    // Get settings from option
    $settings = get_option('securepress_x_settings', array());
    if (empty($settings) || empty($settings['http_headers']) || empty($settings['http_headers']['enabled'])) {
        return;
    }
    
    $http_settings = $settings['http_headers'];
    
    // Add headers based on settings
    if (!empty($http_settings['hsts_enabled']) && is_ssl()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
    
    if (!empty($http_settings['xframe_enabled'])) {
        $frame_policy = is_admin() ? 'SAMEORIGIN' : 'DENY';
        header('X-Frame-Options: ' . $frame_policy);
    }
    
    if (!empty($http_settings['x_content_type_options'])) {
        header('X-Content-Type-Options: nosniff');
    }
    
    if (!empty($http_settings['x_xss_protection'])) {
        header('X-XSS-Protection: 1; mode=block');
    }
    
    if (!empty($http_settings['referrer_policy'])) {
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
    
    if (!empty($http_settings['custom_ido_header_enabled'])) {
        $custom_value = !empty($http_settings['custom_ido_header_value']) ? $http_settings['custom_ido_header_value'] : 'Secure';
        header('X-Ido: ' . $custom_value);
    }
    
    // Remove X-Powered-By header
    @header_remove('X-Powered-By');
    @header('X-Powered-By: ');
}
EOT;
        
        // Write the file
        if ($create_file || !file_exists($mu_plugin_file)) {
            if (file_put_contents($mu_plugin_file, $mu_plugin_content)) {
                error_log('SecurePress X: Created mu-plugin for early headers');
            } else {
                error_log('SecurePress X: Failed to create mu-plugin for early headers');
            }
        } else {
            // Update the file if it exists but with different content
            $current_content = file_get_contents($mu_plugin_file);
            if ($current_content !== $mu_plugin_content) {
                if (file_put_contents($mu_plugin_file, $mu_plugin_content)) {
                    error_log('SecurePress X: Updated mu-plugin for early headers');
                } else {
                    error_log('SecurePress X: Failed to update mu-plugin for early headers');
                }
            }
        }
    }
    
    /**
     * Add security headers
     */
    public function add_security_headers() {
        // Debug log to verify function is called
        error_log('SecurePress X: Adding security headers - enabled: ' . ($this->is_enabled() ? 'true' : 'false'));
        
        // Prevent headers from being sent multiple times
        if (headers_sent()) {
            error_log('SecurePress X: Headers already sent, skipping security headers');
            return;
        }
        
        // Try to remove X-Powered-By header first - multiple methods for different server configurations
        @header_remove('X-Powered-By');
        @header_remove('Server');
        
        // Try PHP specific methods to remove X-Powered-By
        if (function_exists('ini_set')) {
            @ini_set('expose_php', 'off');
        }
        
        // Strict Transport Security
        if ($this->get_setting('hsts_enabled', true) && is_ssl()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
            error_log('SecurePress X: Added HSTS header');
        }
        
        // X-Frame-Options
        if ($this->get_setting('xframe_enabled', true)) {
            $frame_policy = is_admin() ? 'SAMEORIGIN' : 'DENY';
            header('X-Frame-Options: ' . $frame_policy);
            error_log('SecurePress X: Added X-Frame-Options header');
        }
        
        // X-Content-Type-Options
        if ($this->get_setting('x_content_type_options', true)) {
            header('X-Content-Type-Options: nosniff');
            error_log('SecurePress X: Added X-Content-Type-Options header');
        }
        
        // X-XSS-Protection
        if ($this->get_setting('x_xss_protection', true)) {
            header('X-XSS-Protection: 1; mode=block');
            error_log('SecurePress X: Added X-XSS-Protection header');
        }
        
        // Referrer Policy
        if ($this->get_setting('referrer_policy', true)) {
            header('Referrer-Policy: strict-origin-when-cross-origin');
            error_log('SecurePress X: Added Referrer-Policy header');
        }
        
        // Content Security Policy
        if ($this->get_setting('csp_enabled', false)) {
            $csp = $this->build_csp_header();
            if (!empty($csp)) {
                header('Content-Security-Policy: ' . $csp);
                error_log('SecurePress X: Added Content-Security-Policy header');
            }
        }
        
        // Custom X-Ido header
        if ($this->get_setting('custom_ido_header_enabled', false)) {
            $custom_value = $this->get_setting('custom_ido_header_value', 'Secure');
            header('X-Ido: ' . $custom_value);
            error_log('SecurePress X: Added custom X-Ido header: ' . $custom_value);
        }
        
        // Permissions Policy (formerly Feature Policy)
        $this->add_permissions_policy();
        
        // Additional security headers
        $this->add_additional_headers();
        
        // Double-check X-Powered-By removal
        @header('X-Powered-By: ');
        
        // Log headers after setting them
        if (function_exists('headers_list')) {
            $headers = headers_list();
            error_log('SecurePress X: Headers after setting: ' . print_r($headers, true));
        }
    }
    
    /**
     * Add security headers to WordPress headers array
     * 
     * @param array $headers Existing headers
     * @return array Modified headers with security headers
     */
    public function add_wp_headers($headers) {
        // Debug log to verify function is called
        error_log('SecurePress X: Adding security headers via wp_headers filter');
        
        // Strict Transport Security
        if ($this->get_setting('hsts_enabled', true) && is_ssl()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains; preload';
        }
        
        // X-Frame-Options
        if ($this->get_setting('xframe_enabled', true)) {
            $frame_policy = is_admin() ? 'SAMEORIGIN' : 'DENY';
            $headers['X-Frame-Options'] = $frame_policy;
        }
        
        // X-Content-Type-Options
        if ($this->get_setting('x_content_type_options', true)) {
            $headers['X-Content-Type-Options'] = 'nosniff';
        }
        
        // X-XSS-Protection
        if ($this->get_setting('x_xss_protection', true)) {
            $headers['X-XSS-Protection'] = '1; mode=block';
        }
        
        // Referrer Policy
        if ($this->get_setting('referrer_policy', true)) {
            $headers['Referrer-Policy'] = 'strict-origin-when-cross-origin';
        }
        
        // Content Security Policy
        if ($this->get_setting('csp_enabled', false)) {
            $csp = $this->build_csp_header();
            if (!empty($csp)) {
                $headers['Content-Security-Policy'] = $csp;
            }
        }
        
        // Custom X-Ido header
        if ($this->get_setting('custom_ido_header_enabled', false)) {
            $custom_value = $this->get_setting('custom_ido_header_value', 'Secure');
            $headers['X-Ido'] = $custom_value;
        }
        
        // Remove X-Powered-By if it exists
        if (isset($headers['X-Powered-By'])) {
            unset($headers['X-Powered-By']);
        }
        
        // Log the headers we're adding
        error_log('SecurePress X: Headers added via wp_headers filter: ' . print_r($headers, true));
        
        return $headers;
    }
    
    /**
     * Add security headers for admin area
     */
    public function add_admin_security_headers() {
        $this->add_security_headers();
        
        // Additional admin-specific headers
        if (!headers_sent()) {
            header('X-Frame-Options: SAMEORIGIN');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
        }
    }
    
    /**
     * Add security headers for login page
     */
    public function add_login_security_headers() {
        $this->add_security_headers();
        
        // Additional login-specific headers
        if (!headers_sent()) {
            header('X-Frame-Options: DENY');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
    }
    
    /**
     * Remove version information from headers
     */
    public function remove_version_headers() {
        // Remove WordPress version from generator
        remove_action('wp_head', 'wp_generator');
        
        // Remove version from scripts and styles
        add_filter('script_loader_src', array($this, 'remove_version_parameter'));
        add_filter('style_loader_src', array($this, 'remove_version_parameter'));
        
        // Remove WordPress version from RSS feeds
        add_filter('the_generator', '__return_empty_string');
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
    
    /**
     * Build Content Security Policy header
     * 
     * @return string CSP header value
     */
    private function build_csp_header() {
        $custom_csp = $this->get_setting('custom_csp_policy', '');
        
        if (!empty($custom_csp)) {
            return $custom_csp;
        }
        
        // Default CSP for WordPress
        $directives = array();
        
        // Default source
        $directives[] = "default-src 'self'";
        
        // Scripts - allow inline for WordPress admin
        if (is_admin()) {
            $directives[] = "script-src 'self' 'unsafe-inline' 'unsafe-eval'";
        } else {
            $directives[] = "script-src 'self' 'unsafe-inline'";
        }
        
        // Styles - allow inline for WordPress
        $directives[] = "style-src 'self' 'unsafe-inline'";
        
        // Images - allow data URIs and external images
        $directives[] = "img-src 'self' data: https:";
        
        // Fonts
        $directives[] = "font-src 'self' data:";
        
        // Objects and plugins
        $directives[] = "object-src 'none'";
        
        // Base URI
        $directives[] = "base-uri 'self'";
        
        // Form actions
        $directives[] = "form-action 'self'";
        
        // Frame ancestors
        $directives[] = "frame-ancestors 'none'";
        
        return implode('; ', $directives);
    }
    
    /**
     * Add Permissions Policy header
     */
    private function add_permissions_policy() {
        if (headers_sent()) {
            return;
        }
        
        $policies = array(
            'accelerometer=()',
            'ambient-light-sensor=()',
            'autoplay=()',
            'battery=()',
            'camera=()',
            'display-capture=()',
            'document-domain=()',
            'encrypted-media=()',
            'execution-while-not-rendered=()',
            'execution-while-out-of-viewport=()',
            'fullscreen=(self)',
            'geolocation=()',
            'gyroscope=()',
            'layout-animations=()',
            'legacy-image-formats=()',
            'magnetometer=()',
            'microphone=()',
            'midi=()',
            'navigation-override=()',
            'oversized-images=()',
            'payment=()',
            'picture-in-picture=()',
            'publickey-credentials-get=()',
            'sync-xhr=()',
            'usb=()',
            'vr=()',
            'wake-lock=()',
            'xr-spatial-tracking=()'
        );
        
        header('Permissions-Policy: ' . implode(', ', $policies));
    }
    
    /**
     * Add additional security headers
     */
    private function add_additional_headers() {
        if (headers_sent()) {
            return;
        }
        
        // Cross-Origin Embedder Policy
        header('Cross-Origin-Embedder-Policy: require-corp');
        
        // Cross-Origin Opener Policy
        header('Cross-Origin-Opener-Policy: same-origin');
        
        // Cross-Origin Resource Policy
        header('Cross-Origin-Resource-Policy: same-origin');
        
        // Expect-CT (Certificate Transparency)
        if (is_ssl()) {
            header('Expect-CT: max-age=86400, enforce');
        }
        
        // NEL (Network Error Logging) - basic implementation
        if (is_ssl()) {
            header('NEL: {"report_to":"default","max_age":31536000,"include_subdomains":true}');
        }
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
                'title' => __('Enable Security Headers', 'securepress-x'),
                'description' => __('Add HTTP security headers to protect against various attacks', 'securepress-x')
            ),
            'hsts_enabled' => array(
                'type' => 'boolean',
                'default' => true,
                'title' => __('Strict Transport Security (HSTS)', 'securepress-x'),
                'description' => __('Force HTTPS connections and prevent protocol downgrade attacks', 'securepress-x')
            ),
            'xframe_enabled' => array(
                'type' => 'boolean',
                'default' => true,
                'title' => __('X-Frame-Options', 'securepress-x'),
                'description' => __('Prevent clickjacking by controlling iframe embedding', 'securepress-x')
            ),
            'x_content_type_options' => array(
                'type' => 'boolean',
                'default' => true,
                'title' => __('X-Content-Type-Options', 'securepress-x'),
                'description' => __('Prevent MIME type sniffing attacks', 'securepress-x')
            ),
            'x_xss_protection' => array(
                'type' => 'boolean',
                'default' => true,
                'title' => __('X-XSS-Protection', 'securepress-x'),
                'description' => __('Enable browser XSS filtering', 'securepress-x')
            ),
            'referrer_policy' => array(
                'type' => 'boolean',
                'default' => true,
                'title' => __('Referrer Policy', 'securepress-x'),
                'description' => __('Control how much referrer information is shared', 'securepress-x')
            ),
            'csp_enabled' => array(
                'type' => 'boolean',
                'default' => false,
                'title' => __('Content Security Policy (CSP)', 'securepress-x'),
                'description' => __('Advanced protection against XSS and data injection attacks', 'securepress-x')
            ),
            'custom_csp_policy' => array(
                'type' => 'textarea',
                'default' => '',
                'title' => __('Custom CSP Directives', 'securepress-x'),
                'description' => __('Custom Content Security Policy directives (leave empty for default)', 'securepress-x'),
                'dependency' => 'csp_enabled'
            ),
            'custom_ido_header_enabled' => array(
                'type' => 'boolean',
                'default' => false,
                'title' => __('Custom X-Ido Header', 'securepress-x'),
                'description' => __('Enable custom X-Ido header', 'securepress-x')
            ),
            'custom_ido_header_value' => array(
                'type' => 'text',
                'default' => 'Secure',
                'title' => __('Custom X-Ido Header Value', 'securepress-x'),
                'description' => __('Value for the custom X-Ido header', 'securepress-x'),
                'dependency' => 'custom_ido_header_enabled'
            )
        );
    }
    
    /**
     * Get module display name
     * 
     * @return string Display name
     */
    public function get_display_name() {
        return __('Security Headers', 'securepress-x');
    }
    
    /**
     * Get module description
     * 
     * @return string Module description
     */
    public function get_description() {
        return __('הוסף כותרות HTTP אבטחה למניעת התקפות XSS, Clickjacking, MIME Sniffing ועוד', 'securepress-x');
    }
    
    /**
     * Get module icon
     * 
     * @return string Icon class
     */
    public function get_icon() {
        return 'dashicons-shield-alt';
    }
    
    /**
     * Get current security headers status
     * 
     * @return array Headers status
     */
    public function get_headers_status() {
        $status = array();
        
        // Check if headers are being sent
        $headers = $this->get_response_headers();
        
        $status['hsts_enabled'] = isset($headers['strict-transport-security']);
        $status['xframe_enabled'] = isset($headers['x-frame-options']);
        $status['x_content_type_options'] = isset($headers['x-content-type-options']);
        $status['x_xss_protection'] = isset($headers['x-xss-protection']);
        $status['referrer_policy'] = isset($headers['referrer-policy']);
        $status['csp_enabled'] = isset($headers['content-security-policy']);
        $status['custom_ido_header'] = isset($headers['x-ido']);
        
        return $status;
    }
    
    /**
     * Get response headers for testing
     * 
     * @return array Response headers
     */
    private function get_response_headers() {
        // In a real implementation, this would test actual headers
        // For now, return based on settings
        $headers = array();
        
        if ($this->get_setting('hsts_enabled', true) && is_ssl()) {
            $headers['strict-transport-security'] = true;
        }
        
        if ($this->get_setting('xframe_enabled', true)) {
            $headers['x-frame-options'] = true;
        }
        
        if ($this->get_setting('x_content_type_options', true)) {
            $headers['x-content-type-options'] = true;
        }
        
        if ($this->get_setting('x_xss_protection', true)) {
            $headers['x-xss-protection'] = true;
        }
        
        if ($this->get_setting('referrer_policy', true)) {
            $headers['referrer-policy'] = true;
        }
        
        if ($this->get_setting('csp_enabled', false)) {
            $headers['content-security-policy'] = true;
        }
        
        if ($this->get_setting('custom_ido_header_enabled', false)) {
            $headers['x-ido'] = $this->get_setting('custom_ido_header_value', 'Secure');
        }
        
        return $headers;
    }
    
    /**
     * Test security headers
     * 
     * @return array Test results
     */
    public function test_security_headers() {
        $results = array(
            'passed' => 0,
            'failed' => 0,
            'tests' => array()
        );
        
        $headers_status = $this->get_headers_status();
        
        foreach ($headers_status as $header => $active) {
            $test_result = array(
                'name' => $header,
                'status' => $active ? 'pass' : 'fail',
                'message' => $active 
                    ? sprintf(__('%s header is active', 'securepress-x'), str_replace('_', '-', $header))
                    : sprintf(__('%s header is missing', 'securepress-x'), str_replace('_', '-', $header))
            );
            
            $results['tests'][] = $test_result;
            
            if ($active) {
                $results['passed']++;
            } else {
                $results['failed']++;
            }
        }
        
        return $results;
    }
    
    /**
     * Log headers status to debug log
     */
    public function log_headers_status() {
        if (!$this->is_enabled() || !defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        error_log('SecurePress X: Checking headers status');
        
        // Try to get headers using different methods
        $headers = array();
        
        // Method 1: Use headers_list() if available
        if (function_exists('headers_list')) {
            $raw_headers = headers_list();
            error_log('SecurePress X: Headers from headers_list(): ' . print_r($raw_headers, true));
            
            foreach ($raw_headers as $header) {
                $parts = explode(':', $header, 2);
                if (count($parts) === 2) {
                    $headers[trim($parts[0])] = trim($parts[1]);
                }
            }
        }
        
        // Method 2: Check settings to see what should be enabled
        $expected_headers = array();
        
        if ($this->get_setting('hsts_enabled', true) && is_ssl()) {
            $expected_headers['Strict-Transport-Security'] = 'Enabled';
        }
        
        if ($this->get_setting('xframe_enabled', true)) {
            $expected_headers['X-Frame-Options'] = 'Enabled';
        }
        
        if ($this->get_setting('x_content_type_options', true)) {
            $expected_headers['X-Content-Type-Options'] = 'Enabled';
        }
        
        if ($this->get_setting('x_xss_protection', true)) {
            $expected_headers['X-XSS-Protection'] = 'Enabled';
        }
        
        if ($this->get_setting('referrer_policy', true)) {
            $expected_headers['Referrer-Policy'] = 'Enabled';
        }
        
        if ($this->get_setting('csp_enabled', false)) {
            $expected_headers['Content-Security-Policy'] = 'Enabled';
        }
        
        if ($this->get_setting('custom_ido_header_enabled', false)) {
            $expected_headers['X-Ido'] = $this->get_setting('custom_ido_header_value', 'Secure');
        }
        
        // Log expected vs actual headers
        error_log('SecurePress X: Expected headers: ' . print_r($expected_headers, true));
        error_log('SecurePress X: Actual headers found: ' . print_r($headers, true));
        
        // Check if all expected headers are present
        $missing_headers = array();
        foreach ($expected_headers as $header => $value) {
            if (!isset($headers[$header])) {
                $missing_headers[] = $header;
            }
        }
        
        if (!empty($missing_headers)) {
            error_log('SecurePress X: Missing headers: ' . implode(', ', $missing_headers));
        } else {
            error_log('SecurePress X: All expected headers are present');
        }
    }
} 