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

        $settings = get_option('securepress_settings', array());
        $retention_days = isset($settings['logging']['retention_days']) ? (int) $settings['logging']['retention_days'] : 30;

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
     * @param string $severity Event severity (high, medium, low)
     * @return bool
     */
    public static function log($type, $message, $severity = 'low') {
        return self::instance()->log_event($type, $message, $severity);
    }

    /**
     * Internal method to log an event
     *
     * @param string $type Event type
     * @param string $message Event message
     * @param string $severity JSON encoded context data
     * @return bool
     */
    private function log_event($type, $message, $severity = '{}') {
        global $wpdb;

        // Ensure table exists
        if (!$this->table_exists()) {
            error_log('SecurePress X: Log table does not exist. Please deactivate and reactivate the plugin.');
            return false;
        }

        $current_user = wp_get_current_user();
        $user = $current_user->exists() ? $current_user->user_login : 'Guest';

        $data = array(
            'type' => $type,
            'message' => $message,
            'severity' => $severity, // Already JSON encoded
            'ip' => $this->get_client_ip(),
            'user' => $user
        );

        $format = array(
            '%s', // type
            '%s', // message
            '%s', // severity (JSON)
            '%s', // ip
            '%s'  // user
        );

        $result = $wpdb->insert($this->table_name, $data, $format);

        if ($result) {
            $this->maybe_send_alert($type, $message, $severity);
            return true;
        }

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
        $settings = get_option('securepress_settings', array());
        
        if (empty($settings['notifications']['email_alerts'])) {
            return;
        }

        $alert_level = $settings['notifications']['alert_severity_level'];
        
        // Check if we should send an alert based on severity
        if ($alert_level === 'high' && $severity !== 'high') {
            return;
        }
        if ($alert_level === 'medium' && !in_array($severity, array('high', 'medium'))) {
            return;
        }

        $recipients = explode(',', $settings['notifications']['email_recipients']);
        $recipients = array_map('trim', $recipients);
        $recipients = array_filter($recipients);

        if (empty($recipients)) {
            $recipients = array(get_option('admin_email'));
        }

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

        foreach ($recipients as $recipient) {
            wp_mail($recipient, $subject, $body);
        }
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
            'level' => '',
            'search' => '',
            'type' => '',
            'severity' => '',
            'date_from' => '',
            'date_to' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Build WHERE clause
        $where_conditions = array();
        $where_values = array();
        
        if (!empty($args['level'])) {
            $where_conditions[] = "severity = %s";
            $where_values[] = $args['level'];
        }
        
        if (!empty($args['type'])) {
            $where_conditions[] = "type = %s";
            $where_values[] = $args['type'];
        }
        
        if (!empty($args['severity'])) {
            $where_conditions[] = "severity = %s";
            $where_values[] = $args['severity'];
        }
        
        if (!empty($args['search'])) {
            $where_conditions[] = "(message LIKE %s OR type LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        if (!empty($args['date_from'])) {
            $where_conditions[] = "timestamp >= %s";
            $where_values[] = $args['date_from'] . ' 00:00:00';
        }
        
        if (!empty($args['date_to'])) {
            $where_conditions[] = "timestamp <= %s";
            $where_values[] = $args['date_to'] . ' 23:59:59';
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        // Get total count
        $count_query = "SELECT COUNT(*) FROM {$this->table_name} {$where_clause}";
        if (!empty($where_values)) {
            $count_query = $wpdb->prepare($count_query, $where_values);
        }
        $total = (int) $wpdb->get_var($count_query);
        
        // Get logs
        $offset = ($args['page'] - 1) * $args['per_page'];
        $logs_query = "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY timestamp DESC LIMIT %d OFFSET %d";
        $query_values = array_merge($where_values, array($args['per_page'], $offset));
        
        $logs_query = $wpdb->prepare($logs_query, $query_values);
        $logs = $wpdb->get_results($logs_query);
        
        // Format logs
        $formatted_logs = array();
        foreach ($logs as $log) {
            $formatted_logs[] = array(
                'id' => $log->id,
                'type' => $log->type,
                'message' => $log->message,
                'severity' => $log->severity,
                'timestamp' => $log->timestamp,
                'ip' => $log->ip,
                'user' => $log->user
            );
        }
        
        return array(
            'logs' => $formatted_logs,
            'total' => $total
        );
    }
} 