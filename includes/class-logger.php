<?php
/**
 * SecurePress X Logger Class
 * 
 * Handles logging for security events and audit trails
 * 
 * @package SecurePress_X
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Logger class
 */
class SecurePress_Logger {
    
    /**
     * The single instance of the class
     *
     * @var SecurePress_Logger
     */
    protected static $instance = null;

    /**
     * Table name
     *
     * @var string
     */
    private $table_name;

    /**
     * Main SecurePress_Logger Instance
     *
     * @return SecurePress_Logger
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'securepress_log';
        
        // Only schedule cleanup if table exists
        if ($this->table_exists()) {
            add_action('init', array($this, 'schedule_cleanup'));
        }
    }

    /**
     * Check if log table exists
     *
     * @return bool
     */
    private function table_exists() {
        global $wpdb;
        return $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") === $this->table_name;
    }

    /**
     * Schedule cleanup of old logs
     */
    public function schedule_cleanup() {
        if (!wp_next_scheduled('securepress_cleanup_logs')) {
            wp_schedule_event(time(), 'daily', 'securepress_cleanup_logs');
        }

        add_action('securepress_cleanup_logs', array($this, 'cleanup_old_logs'));
    }

    /**
     * Clean up old logs
     */
    public function cleanup_old_logs() {
        global $wpdb;

        // Get settings from the correct option
        $settings = get_option('securepress_x_settings', array());
        $audit_log_settings = isset($settings['audit_log']) ? $settings['audit_log'] : array();
        $retention_days = isset($audit_log_settings['retention_days']) ? (int) $audit_log_settings['retention_days'] : 30;

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $retention_days
            )
        );
    }

    /**
     * Log a security event
     *
     * @param string $type Event type
     * @param string $message Event message
     * @param string $severity Event severity (info, warning, error)
     * @return bool
     */
    public static function log($type, $message, $severity = 'info') {
        return self::instance()->log_event($type, $message, $severity);
    }

    /**
     * Internal method to log an event
     *
     * @param string $type Event type
     * @param string $message Event message
     * @param string $severity Severity level (info, warning, error)
     * @return bool
     */
    private function log_event($type, $message, $severity = 'info') {
        global $wpdb;

        // Ensure table exists
        if (!$this->table_exists()) {
            error_log('SecurePress X: Log table does not exist. Please deactivate and reactivate the plugin.');
            return false;
        }

        // Validate severity
        if (!in_array($severity, array('info', 'warning', 'error'))) {
            $severity = 'info';
        }

        $current_user = wp_get_current_user();
        $user = $current_user->exists() ? $current_user->user_login : 'Guest';

        $data = array(
            'type' => $type,
            'message' => $message,
            'severity' => $severity,
            'ip' => $this->get_client_ip(),
            'user' => $user,
            'timestamp' => current_time('mysql')
        );

        $format = array(
            '%s', // type
            '%s', // message
            '%s', // severity
            '%s', // ip
            '%s', // user
            '%s'  // timestamp
        );

        $result = $wpdb->insert($this->table_name, $data, $format);

        if ($result) {
            $this->maybe_send_alert($type, $message, $severity);
            return true;
        }

        error_log('SecurePress X: Failed to insert log entry: ' . $wpdb->last_error);
        return false;
    }

    /**
     * Send email alert if configured
     *
     * @param string $type
     * @param string $message
     * @param string $severity
     */
    private function maybe_send_alert($type, $message, $severity) {
        // Get settings from the correct option
        $settings = get_option('securepress_x_settings', array());
        $audit_log_settings = isset($settings['audit_log']) ? $settings['audit_log'] : array();
        
        if (empty($audit_log_settings['notifications_enabled']) || $audit_log_settings['notifications_enabled'] !== true) {
            return;
        }

        if ($audit_log_settings['notification_type'] !== 'email' || empty($audit_log_settings['notification_email'])) {
            return;
        }

        // Check if we should send an alert based on severity and log level
        $log_level = isset($audit_log_settings['log_level']) ? $audit_log_settings['log_level'] : 'all';
        
        if ($log_level === 'errors' && $severity !== 'error') {
            return;
        }
        
        if ($log_level === 'warnings' && !in_array($severity, array('error', 'warning'))) {
            return;
        }

        $recipient = $audit_log_settings['notification_email'];

        $subject = sprintf(
            __('[SecurePress X] Security Alert: %s', 'securepress-x'),
            $type
        );

        $body = sprintf(
            __("A security event has been detected:\n\nType: %s\nSeverity: %s\nMessage: %s\nIP: %s\nUser: %s\n\nTime: %s", 'securepress-x'),
            $type,
            $severity,
            $message,
            $this->get_client_ip(),
            wp_get_current_user()->user_login,
            current_time('mysql')
        );

        wp_mail($recipient, $subject, $body);
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    private function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return sanitize_text_field($ip);
    }
    
    /**
     * Get logs with filtering and pagination
     *
     * @param array $args Query arguments
     * @return array
     */
    public function get_logs($args = array()) {
        global $wpdb;
        
        if (!$this->table_exists()) {
            return array('logs' => array(), 'total' => 0);
        }
        
        $defaults = array(
            'page' => 1,
            'per_page' => 20,
            'search' => '',
            'type' => '',
            'severity' => '',
            'date_from' => '',
            'date_to' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array('1=1');
        $where_args = array();
        
        if (!empty($args['search'])) {
            $where[] = '(message LIKE %s OR type LIKE %s OR user LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_args[] = $search_term;
            $where_args[] = $search_term;
            $where_args[] = $search_term;
        }
        
        if (!empty($args['type'])) {
            $where[] = 'type = %s';
            $where_args[] = $args['type'];
        }
        
        if (!empty($args['severity'])) {
            $where[] = 'severity = %s';
            $where_args[] = $args['severity'];
        }
        
        if (!empty($args['date_from'])) {
            $where[] = 'timestamp >= %s';
            $where_args[] = $args['date_from'] . ' 00:00:00';
        }
        
        if (!empty($args['date_to'])) {
            $where[] = 'timestamp <= %s';
            $where_args[] = $args['date_to'] . ' 23:59:59';
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Count total records
        $count_query = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}";
        $total = $wpdb->get_var($wpdb->prepare($count_query, $where_args));
        
        // Get paginated results
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        $query = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY timestamp DESC LIMIT %d OFFSET %d";
        $prepared_args = array_merge($where_args, array($args['per_page'], $offset));
        
        $logs = $wpdb->get_results($wpdb->prepare($query, $prepared_args));
        
        return array(
            'logs' => $logs,
            'total' => (int) $total,
            'pages' => ceil($total / $args['per_page'])
        );
    }
} 