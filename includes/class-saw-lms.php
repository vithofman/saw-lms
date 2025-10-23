<?php
/**
 * Hlavní třída pluginu - Singleton pattern
 * Řídí načítání všech komponent a hooks
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAW_LMS {
    
    /**
     * Instance třídy (Singleton)
     */
    private static $instance = null;
    
    /**
     * Loader - manager pro hooks
     */
    protected $loader;
    
    /**
     * Verze pluginu
     */
    protected $version;
    
    /**
     * Plugin slug
     */
    protected $plugin_name;
    
    /**
     * Privátní konstruktor (Singleton pattern)
     */
    private function __construct() {
        $this->version = SAW_LMS_VERSION;
        $this->plugin_name = 'saw-lms';
        
        $this->load_dependencies();
        $this->define_admin_hooks();
    }
    
    /**
     * Získání instance (Singleton)
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Načtení závislostí
     */
    private function load_dependencies() {
        // Načtení Loaderu pro hooks
        require_once SAW_LMS_PLUGIN_DIR . 'includes/class-loader.php';
        $this->loader = new SAW_LMS_Loader();
        
        // Načtení Admin menu
        require_once SAW_LMS_PLUGIN_DIR . 'admin/class-admin-menu.php';
    }
    
    /**
     * Definice admin hooks
     */
    private function define_admin_hooks() {
        // Admin menu
        $admin_menu = new SAW_LMS_Admin_Menu($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('admin_menu', $admin_menu, 'add_menu');
        
        // Admin notices (např. pro úspěšnou aktivaci)
        $this->loader->add_action('admin_notices', $this, 'activation_notice');
    }
    
    /**
     * Spuštění pluginu - zavolá všechny zaregistrované hooks
     */
    public function run() {
        $this->loader->run();
    }
    
    /**
     * Zobrazení notice po aktivaci
     */
    public function activation_notice() {
        // Zobrazit notice pouze pokud byla aktivace úspěšná
        if (get_transient('saw_lms_activation_notice')) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong><?php _e('SAW LMS aktivován!', 'saw-lms'); ?></strong>
                    <?php _e('Databázové tabulky byly úspěšně vytvořeny.', 'saw-lms'); ?>
                    <a href="<?php echo admin_url('admin.php?page=saw-lms'); ?>">
                        <?php _e('Přejít na Dashboard', 'saw-lms'); ?>
                    </a>
                </p>
            </div>
            <?php
            // Smazat transient aby se notice zobrazil jen jednou
            delete_transient('saw_lms_activation_notice');
        }
    }
    
    /**
     * Gettery
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }
    
    public function get_loader() {
        return $this->loader;
    }
    
    public function get_version() {
        return $this->version;
    }
}