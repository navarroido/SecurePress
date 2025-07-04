<?php
/**
 * Main plugin class
 *
 * @package SecurePressX
 */

defined('ABSPATH') || exit;

/**
 * Main SecurePress X Class
 */
final class SecurePress_X {
    /**
     * Plugin version
     *
     * @var string
     */
    public $version = '1.0.0';

    /**
     * The single instance of the class
     *
     * @var SecurePress_X
     */
    protected static $instance = null;

    /**
     * Main SecurePress_X Instance
     *
     * @return SecurePress_X
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
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Define constants
     */
    private function define_constants() {
        $this->define('SECUREPRESS_VERSION', $this->version);
        $this->define('SECUREPRESS_PLUGIN_DIR', plugin_dir_path(SECUREPRESS_PLUGIN_FILE));
        $this->define('SECUREPRESS_PLUGIN_URL', plugin_dir_url(SECUREPRESS_PLUGIN_FILE));
    }

    /**
     * Define constant if not already defined
     *
     * @param string $name
     * @param string|bool $value
     */
    private function define($name, $value) {
        if (!defined($name)) {
            define($name, $value);
        }
    }

    /**
     * Include required files
     */
    private function includes() {
        // Core classes
        require_once SECUREPRESS_PLUGIN_DIR . 'includes/class-module.php';
        require_once SECUREPRESS_PLUGIN_DIR . 'includes/class-rest-api.php';
        require_once SECUREPRESS_PLUGIN_DIR . 'includes/class-logger.php';

        // Admin
        if (is_admin()) {
            require_once SECUREPRESS_PLUGIN_DIR . 'includes/admin/class-admin.php';
        }

        // Modules
        require_once SECUREPRESS_PLUGIN_DIR . 'includes/modules/class-bruteforce-protection.php';
        require_once SECUREPRESS_PLUGIN_DIR . 'includes/modules/class-security-hardening.php';
        require_once SECUREPRESS_PLUGIN_DIR . 'includes/modules/class-malware-scanner.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'));
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize modules
        $this->init_modules();

        // Initialize REST API
        SecurePress_REST_API::instance();

        // Initialize admin
        if (is_admin()) {
            SecurePress_Admin::instance();
        }

        // Load text domain
        load_plugin_textdomain('securepress-x', false, dirname(plugin_basename(SECUREPRESS_PLUGIN_FILE)) . '/languages');
    }

    /**
     * Initialize modules
     */
    private function init_modules() {
        $this->modules = array(
            'bruteforce' => new SecurePress_Bruteforce_Protection(),
            'hardening' => new SecurePress_Security_Hardening(),
            'malware' => new SecurePress_Malware_Scanner()
        );

        foreach ($this->modules as $module) {
            $module->init();
        }
    }

    /**
     * Get module by ID
     *
     * @param string $id
     * @return SecurePress_Module|null
     */
    public function get_module($id) {
        return isset($this->modules[$id]) ? $this->modules[$id] : null;
    }

    /**
     * Get all modules
     *
     * @return array
     */
    public function get_modules() {
        return $this->modules;
    }
} 