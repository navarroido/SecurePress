<?php
/**
 * SecurePress X Installer Class
 * 
 * Handles plugin activation, deactivation, and database setup
 * 
 * @package SecurePress_X
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin installer class
 */
class SecurePress_Installer {
    
    /**
     * Plugin activation
     */
    public static function activate() {
        try {
            // Create database tables
            self::create_tables();
            
            // Setup user capabilities
            self::setup_capabilities();
            
            // Create upload directory
            self::create_upload_directory();
            
            // Set activation flag
            update_option('securepress_x_activated', time());
            update_option('securepress_x_version', SECUREPRESS_X_VERSION);
            
            // Log success
            error_log('SecurePress X activation completed successfully');
            
        } catch (Exception $e) {
            // Log any errors
            error_log('SecurePress X activation error: ' . $e->getMessage());
            wp_die('Error during plugin activation: ' . esc_html($e->getMessage()));
        }
    }
    
    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        // Clear scheduled hooks
        wp_clear_scheduled_hook('securepress_file_integrity_check');
        wp_clear_scheduled_hook('securepress_cleanup_logs');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin uninstall
     */
    public static function uninstall() {
        // Check if we should delete data
        $delete_data = get_option('securepress_x_delete_data_on_uninstall', false);
        
        if ($delete_data) {
            // Drop custom tables
            self::drop_tables();
            
            // Delete options
            self::delete_options();
            
            // Remove upload directory
            self::remove_upload_directory();
            
            // Remove capabilities
            self::remove_capabilities();
        }
    }
    
    /**
     * Create custom database tables
     */
    private static function create_tables() {
        try {
            global $wpdb;
            $charset_collate = $wpdb->get_charset_collate();
            
            // Create log table
            $table_log = $wpdb->prefix . 'securepress_log';
            
            $sql_log = "CREATE TABLE IF NOT EXISTS $table_log (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                type varchar(50) NOT NULL,
                message text NOT NULL,
                severity varchar(20) NOT NULL,
                timestamp datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                ip varchar(45) NOT NULL,
                user varchar(100) NOT NULL,
                PRIMARY KEY  (id),
                KEY type (type),
                KEY severity (severity),
                KEY timestamp (timestamp)
            ) $charset_collate;";
            
            // Create failed logins table
            $table_failed_logins = $wpdb->prefix . 'securepress_failed_logins';
            
            $sql_failed_logins = "CREATE TABLE IF NOT EXISTS $table_failed_logins (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                ip_address varchar(45) NOT NULL,
                username varchar(255) NOT NULL,
                attempt_time datetime NOT NULL,
                user_agent varchar(255),
                locked_until datetime NULL,
                attempts int(11) NOT NULL DEFAULT 1,
                PRIMARY KEY (id),
                KEY ip_address (ip_address),
                KEY attempt_time (attempt_time)
            ) $charset_collate;";
            
            // Create file integrity table
            $table_file_integrity = $wpdb->prefix . 'securepress_file_integrity';
            
            $sql_file_integrity = "CREATE TABLE IF NOT EXISTS $table_file_integrity (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                file_path varchar(512) NOT NULL,
                file_hash varchar(64) NOT NULL,
                file_type varchar(20) NOT NULL,
                last_checked datetime NOT NULL,
                status varchar(20) NOT NULL DEFAULT 'ok',
                PRIMARY KEY (id),
                UNIQUE KEY file_path (file_path),
                KEY file_type (file_type),
                KEY status (status)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            
            // Create tables
            dbDelta($sql_log);
            dbDelta($sql_failed_logins);
            dbDelta($sql_file_integrity);
            
            // Save database version
            update_option('securepress_x_db_version', '1.0.0');
            
        } catch (Exception $e) {
            error_log('SecurePress X table creation error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Drop custom tables
     */
    private static function drop_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'securepress_log',
            $wpdb->prefix . 'securepress_failed_logins',
            $wpdb->prefix . 'securepress_file_integrity'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }
    
    /**
     * Setup user capabilities
     */
    private static function setup_capabilities() {
        $admin_role = get_role('administrator');
        
        if ($admin_role) {
            $admin_role->add_cap('manage_securepress');
            $admin_role->add_cap('view_securepress_logs');
            $admin_role->add_cap('configure_securepress');
        }
    }
    
    /**
     * Remove user capabilities
     */
    private static function remove_capabilities() {
        global $wp_roles;
        
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }
        
        foreach ($wp_roles->roles as $role_name => $role_info) {
            $role = get_role($role_name);
            if ($role) {
                $role->remove_cap('manage_securepress');
                $role->remove_cap('view_securepress_logs');
                $role->remove_cap('configure_securepress');
            }
        }
    }
    
    /**
     * Create upload directory for plugin files
     */
    private static function create_upload_directory() {
        $upload_dir = wp_upload_dir();
        $securepress_dir = $upload_dir['basedir'] . '/securepress-x';
        
        if (!file_exists($securepress_dir)) {
            wp_mkdir_p($securepress_dir);
            
            // Create .htaccess to protect directory
            $htaccess_content = "Order deny,allow\nDeny from all\n";
            file_put_contents($securepress_dir . '/.htaccess', $htaccess_content);
            
            // Create index.php for extra protection
            file_put_contents($securepress_dir . '/index.php', '<?php // Silence is golden');
        }
    }
    
    /**
     * Remove upload directory
     */
    private static function remove_upload_directory() {
        $upload_dir = wp_upload_dir();
        $securepress_dir = $upload_dir['basedir'] . '/securepress-x';
        
        if (file_exists($securepress_dir)) {
            self::delete_directory($securepress_dir);
        }
    }
    
    /**
     * Recursively delete directory
     * 
     * @param string $dir Directory path
     */
    private static function delete_directory($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                self::delete_directory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }
    
    /**
     * Delete plugin options
     */
    private static function delete_options() {
        $options = array(
            'securepress_x_settings',
            'securepress_x_version',
            'securepress_x_db_version',
            'securepress_x_activated',
            'securepress_x_delete_data_on_uninstall'
        );
        
        foreach ($options as $option) {
            delete_option($option);
        }
    }
} 