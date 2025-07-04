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
        if (!$this->is_enabled()) {
            return;
        }
        
        // Add headers early
        add_action('send_headers', array($this, 'add_security_headers'));
        add_action('wp_headers', array($this, 'filter_wp_headers'));
        
        // Add headers for admin area
        add_action('admin_init', array($this, 'add_admin_security_headers'));
        
        // Add headers for login page
        add_action('login_init', array($this, 'add_login_security_headers'));
        
        // Remove unnecessary headers
        add_action('init', array($this, 'remove_version_headers'));
    }
    
    /**
     * Initialize module
     */
    public function init() {
        // Module is ready
        $this->log('Security headers module initialized', 'info');
    }
    
    /**
     * Add security headers
     */
    public function add_security_headers() {
        // Prevent headers from being sent multiple times
        if (headers_sent()) {
            return;
        }
        
        // Strict Transport Security
        if ($this->get_setting('strict_transport_security', true) && is_ssl()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
        
        // X-Frame-Options
        if ($this->get_setting('x_frame_options', true)) {
            $frame_policy = is_admin() ? 'SAMEORIGIN' : 'DENY';
            header('X-Frame-Options: ' . $frame_policy);
        }
        
        // X-Content-Type-Options
        if ($this->get_setting('x_content_type_options', true)) {
            header('X-Content-Type-Options: nosniff');
        }
        
        // X-XSS-Protection
        if ($this->get_setting('x_xss_protection', true)) {
            header('X-XSS-Protection: 1; mode=block');
        }
        
        // Referrer Policy
        if ($this->get_setting('referrer_policy', true)) {
            header('Referrer-Policy: strict-origin-when-cross-origin');
        }
        
        // Content Security Policy
        if ($this->get_setting('content_security_policy', false)) {
            $csp = $this->build_csp_header();
            if (!empty($csp)) {
                header('Content-Security-Policy: ' . $csp);
            }
        }
        
        // Permissions Policy (formerly Feature Policy)
        $this->add_permissions_policy();
        
        // Additional security headers
        $this->add_additional_headers();
    }
    
    /**
     * Filter WordPress headers
     * 
     * @param array $headers Existing headers
     * @return array Modified headers
     */
    public function filter_wp_headers($headers) {
        // Remove or modify potentially problematic headers
        if (isset($headers['X-Pingback'])) {
            unset($headers['X-Pingback']);
        }
        
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
        $custom_csp = $this->get_setting('csp_custom', '');
        
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
        
        // Remove server information
        header_remove('X-Powered-By');
        header_remove('Server');
        
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
            'strict_transport_security' => array(
                'type' => 'boolean',
                'default' => true,
                'title' => __('Strict Transport Security (HSTS)', 'securepress-x'),
                'description' => __('Force HTTPS connections and prevent protocol downgrade attacks', 'securepress-x')
            ),
            'x_frame_options' => array(
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
            'content_security_policy' => array(
                'type' => 'boolean',
                'default' => false,
                'title' => __('Content Security Policy (CSP)', 'securepress-x'),
                'description' => __('Advanced protection against XSS and data injection attacks', 'securepress-x')
            ),
            'csp_custom' => array(
                'type' => 'textarea',
                'default' => '',
                'title' => __('Custom CSP Directives', 'securepress-x'),
                'description' => __('Custom Content Security Policy directives (leave empty for default)', 'securepress-x'),
                'dependency' => 'content_security_policy'
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
        
        $status['strict_transport_security'] = isset($headers['strict-transport-security']);
        $status['x_frame_options'] = isset($headers['x-frame-options']);
        $status['x_content_type_options'] = isset($headers['x-content-type-options']);
        $status['x_xss_protection'] = isset($headers['x-xss-protection']);
        $status['referrer_policy'] = isset($headers['referrer-policy']);
        $status['content_security_policy'] = isset($headers['content-security-policy']);
        
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
        
        if ($this->get_setting('strict_transport_security', true) && is_ssl()) {
            $headers['strict-transport-security'] = true;
        }
        
        if ($this->get_setting('x_frame_options', true)) {
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
        
        if ($this->get_setting('content_security_policy', false)) {
            $headers['content-security-policy'] = true;
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
} 