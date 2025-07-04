<?php
/**
 * SecurePress X Utilities Class
 * 
 * Common utility functions used throughout the plugin
 * 
 * @package SecurePress_X
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Utilities class
 */
class SecurePress_Utilities {
    
    /**
     * Get client IP address
     * 
     * @return string Client IP address
     */
    public static function get_client_ip() {
        $ip_headers = array(
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        );
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                // Handle comma-separated IPs (X-Forwarded-For)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        // Fallback to REMOTE_ADDR even if it's private
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
    
    /**
     * Get user agent string
     * 
     * @return string User agent
     */
    public static function get_user_agent() {
        return isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';
    }
    
    /**
     * Generate secure random string
     * 
     * @param int $length String length
     * @param bool $special_chars Include special characters
     * @return string Random string
     */
    public static function generate_random_string($length = 12, $special_chars = false) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        
        if ($special_chars) {
            $chars .= '!@#$%^&*()_+-=[]{}|;:,.<>?';
        }
        
        $result = '';
        $chars_length = strlen($chars);
        
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[wp_rand(0, $chars_length - 1)];
        }
        
        return $result;
    }
    
    /**
     * Hash string using WordPress standards
     * 
     * @param string $string String to hash
     * @param string $salt Optional salt
     * @return string Hashed string
     */
    public static function hash_string($string, $salt = '') {
        if (empty($salt)) {
            $salt = wp_salt('secure_auth');
        }
        
        return hash('sha256', $string . $salt);
    }
    
    /**
     * Verify string hash
     * 
     * @param string $string Original string
     * @param string $hash Hash to verify against
     * @param string $salt Optional salt
     * @return bool True if hash matches
     */
    public static function verify_hash($string, $hash, $salt = '') {
        return hash_equals($hash, self::hash_string($string, $salt));
    }
    
    /**
     * Check if IP is in whitelist
     * 
     * @param string $ip IP address to check
     * @param array $whitelist Array of whitelisted IPs/ranges
     * @return bool True if IP is whitelisted
     */
    public static function is_ip_whitelisted($ip, $whitelist = array()) {
        if (empty($whitelist)) {
            return false;
        }
        
        foreach ($whitelist as $allowed_ip) {
            if (self::ip_in_range($ip, $allowed_ip)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if IP is in a given range
     * 
     * @param string $ip IP address to check
     * @param string $range IP range (CIDR or single IP)
     * @return bool True if IP is in range
     */
    public static function ip_in_range($ip, $range) {
        if ($ip === $range) {
            return true;
        }
        
        // Handle CIDR notation
        if (strpos($range, '/') !== false) {
            list($subnet, $bits) = explode('/', $range);
            
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                // IPv4
                $ip_long = ip2long($ip);
                $subnet_long = ip2long($subnet);
                $mask = -1 << (32 - $bits);
                
                return ($ip_long & $mask) === ($subnet_long & $mask);
            } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                // IPv6 - simplified check
                return substr(inet_pton($ip), 0, $bits / 8) === substr(inet_pton($subnet), 0, $bits / 8);
            }
        }
        
        return false;
    }
    
    /**
     * Sanitize file path
     * 
     * @param string $path File path to sanitize
     * @return string Sanitized path
     */
    public static function sanitize_file_path($path) {
        // Remove null bytes
        $path = str_replace(chr(0), '', $path);
        
        // Remove directory traversal attempts
        $path = preg_replace('/\.\.+/', '.', $path);
        
        // Normalize slashes
        $path = str_replace('\\', '/', $path);
        
        // Remove duplicate slashes
        $path = preg_replace('/\/+/', '/', $path);
        
        return $path;
    }
    
    /**
     * Get file hash
     * 
     * @param string $file_path Path to file
     * @return string|false File hash or false on failure
     */
    public static function get_file_hash($file_path) {
        if (!file_exists($file_path) || !is_readable($file_path)) {
            return false;
        }
        
        return hash_file('sha256', $file_path);
    }
    
    /**
     * Check if request is AJAX
     * 
     * @return bool True if AJAX request
     */
    public static function is_ajax_request() {
        return defined('DOING_AJAX') && DOING_AJAX;
    }
    
    /**
     * Check if request is from WordPress admin
     * 
     * @return bool True if admin request
     */
    public static function is_admin_request() {
        return is_admin() && !self::is_ajax_request();
    }
    
    /**
     * Check if request is from WordPress login page
     * 
     * @return bool True if login page request
     */
    public static function is_login_request() {
        return in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php'));
    }
    
    /**
     * Format bytes to human readable format
     * 
     * @param int $bytes Number of bytes
     * @param int $precision Decimal precision
     * @return string Formatted string
     */
    public static function format_bytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Get WordPress installation type
     * 
     * @return string Installation type (single, multisite, etc.)
     */
    public static function get_installation_type() {
        if (is_multisite()) {
            return 'multisite';
        }
        
        return 'single';
    }
    
    /**
     * Check if current user can manage SecurePress
     * 
     * @return bool True if user has permission
     */
    public static function current_user_can_manage() {
        return current_user_can('manage_securepress') || current_user_can('manage_options');
    }
    
    /**
     * Get server information
     * 
     * @return array Server information
     */
    public static function get_server_info() {
        global $wp_version;
        
        return array(
            'php_version' => phpversion(),
            'wp_version' => $wp_version,
            'server_software' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown',
            'mysql_version' => $GLOBALS['wpdb']->db_version(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
        );
    }
    
    /**
     * Send notification
     * 
     * @param string $method Notification method (email, webhook, etc.)
     * @param string $subject Subject/title
     * @param string $message Message content
     * @param array $data Additional data
     * @return bool Success status
     */
    public static function send_notification($method, $subject, $message, $data = array()) {
        switch ($method) {
            case 'email':
                return self::send_email_notification($subject, $message, $data);
                
            case 'webhook':
                return self::send_webhook_notification($subject, $message, $data);
                
            case 'slack':
                return self::send_slack_notification($subject, $message, $data);
                
            default:
                return false;
        }
    }
    
    /**
     * Send email notification
     * 
     * @param string $subject Email subject
     * @param string $message Email message
     * @param array $data Additional data
     * @return bool Success status
     */
    private static function send_email_notification($subject, $message, $data = array()) {
        $to = isset($data['email']) ? $data['email'] : get_option('admin_email');
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        return wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Send webhook notification
     * 
     * @param string $subject Subject
     * @param string $message Message
     * @param array $data Additional data
     * @return bool Success status
     */
    private static function send_webhook_notification($subject, $message, $data = array()) {
        if (empty($data['webhook_url'])) {
            return false;
        }
        
        $payload = array(
            'subject' => $subject,
            'message' => $message,
            'site_url' => home_url(),
            'timestamp' => current_time('mysql'),
            'data' => $data
        );
        
        $response = wp_remote_post($data['webhook_url'], array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode($payload),
            'timeout' => 30
        ));
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
    
    /**
     * Send Slack notification
     * 
     * @param string $subject Subject
     * @param string $message Message
     * @param array $data Additional data
     * @return bool Success status
     */
    private static function send_slack_notification($subject, $message, $data = array()) {
        if (empty($data['slack_webhook'])) {
            return false;
        }
        
        $payload = array(
            'text' => $subject,
            'attachments' => array(
                array(
                    'color' => 'warning',
                    'text' => $message,
                    'footer' => home_url(),
                    'ts' => time()
                )
            )
        );
        
        $response = wp_remote_post($data['slack_webhook'], array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode($payload),
            'timeout' => 30
        ));
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
} 