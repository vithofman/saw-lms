<?php
/**
 * Admin Menu - vytvoření menu v administraci
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAW_LMS_Admin_Menu {
    
    /**
     * Plugin name
     */
    private $plugin_name;
    
    /**
     * Plugin version
     */
    private $version;
    
    /**
     * Konstruktor
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }
    
    /**
     * Přidání menu do admin panelu
     */
    public function add_menu() {
        // Hlavní menu položka
        add_menu_page(
            __('SAW LMS', 'saw-lms'),                    // Page title
            __('SAW LMS', 'saw-lms'),                    // Menu title
            'manage_options',                             // Capability
            'saw-lms',                                    // Menu slug
            array($this, 'display_dashboard'),            // Callback
            'dashicons-book-alt',                         // Icon
            30                                            // Position
        );
        
        // Submenu - Dashboard (přejmenovaná první položka)
        add_submenu_page(
            'saw-lms',
            __('Dashboard', 'saw-lms'),
            __('Dashboard', 'saw-lms'),
            'manage_options',
            'saw-lms',
            array($this, 'display_dashboard')
        );
        
        // Submenu - Info (pro MVP)
        add_submenu_page(
            'saw-lms',
            __('Plugin Info', 'saw-lms'),
            __('Plugin Info', 'saw-lms'),
            'manage_options',
            'saw-lms-info',
            array($this, 'display_info_page')
        );
    }
    
    /**
     * Zobrazení Dashboard stránky
     */
    public function display_dashboard() {
        // Kontrola oprávnění
        if (!current_user_can('manage_options')) {
            wp_die(__('Nemáte oprávnění přistupovat k této stránce.', 'saw-lms'));
        }
        
        global $wpdb;
        
        // Načtení základních statistik
        $stats = array(
            'enrollments' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}saw_lms_enrollments"),
            'active_enrollments' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}saw_lms_enrollments WHERE status = 'enrolled'"),
            'completed' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}saw_lms_enrollments WHERE status = 'completed'"),
            'certificates' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}saw_lms_certificates"),
            'groups' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}saw_lms_groups"),
        );
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div style="background: #fff; padding: 20px; margin: 20px 0; border-left: 4px solid #2271b1;">
                <h2><?php _e('🎉 Plugin je aktivní!', 'saw-lms'); ?></h2>
                <p><?php _e('Všechny databázové tabulky byly úspěšně vytvořeny.', 'saw-lms'); ?></p>
                <p><?php _e('Toto je MVP verze (Fáze 0-1 z Development Planu).', 'saw-lms'); ?></p>
            </div>
            
            <h2><?php _e('📊 Rychlý přehled', 'saw-lms'); ?></h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
                
                <!-- Widget: Celkem zápisů -->
                <div style="background: #fff; padding: 20px; border-left: 4px solid #2271b1;">
                    <h3 style="margin: 0 0 10px 0; font-size: 14px; color: #666;">
                        <?php _e('Celkem zápisů', 'saw-lms'); ?>
                    </h3>
                    <p style="margin: 0; font-size: 32px; font-weight: bold; color: #2271b1;">
                        <?php echo number_format($stats['enrollments']); ?>
                    </p>
                </div>
                
                <!-- Widget: Aktivní -->
                <div style="background: #fff; padding: 20px; border-left: 4px solid #00a32a;">
                    <h3 style="margin: 0 0 10px 0; font-size: 14px; color: #666;">
                        <?php _e('Aktivní kurzy', 'saw-lms'); ?>
                    </h3>
                    <p style="margin: 0; font-size: 32px; font-weight: bold; color: #00a32a;">
                        <?php echo number_format($stats['active_enrollments']); ?>
                    </p>
                </div>
                
                <!-- Widget: Dokončeno -->
                <div style="background: #fff; padding: 20px; border-left: 4px solid #dba617;">
                    <h3 style="margin: 0 0 10px 0; font-size: 14px; color: #666;">
                        <?php _e('Dokončeno', 'saw-lms'); ?>
                    </h3>
                    <p style="margin: 0; font-size: 32px; font-weight: bold; color: #dba617;">
                        <?php echo number_format($stats['completed']); ?>
                    </p>
                </div>
                
                <!-- Widget: Certifikáty -->
                <div style="background: #fff; padding: 20px; border-left: 4px solid #d63638;">
                    <h3 style="margin: 0 0 10px 0; font-size: 14px; color: #666;">
                        <?php _e('Certifikáty', 'saw-lms'); ?>
                    </h3>
                    <p style="margin: 0; font-size: 32px; font-weight: bold; color: #d63638;">
                        <?php echo number_format($stats['certificates']); ?>
                    </p>
                </div>
                
                <!-- Widget: Skupiny -->
                <div style="background: #fff; padding: 20px; border-left: 4px solid #72aee6;">
                    <h3 style="margin: 0 0 10px 0; font-size: 14px; color: #666;">
                        <?php _e('Skupinové licence', 'saw-lms'); ?>
                    </h3>
                    <p style="margin: 0; font-size: 32px; font-weight: bold; color: #72aee6;">
                        <?php echo number_format($stats['groups']); ?>
                    </p>
                </div>
                
            </div>
            
            <div style="background: #fffbcc; padding: 15px; margin: 20px 0; border-left: 4px solid #dba617;">
                <h3><?php _e('🚧 V současné době:', 'saw-lms'); ?></h3>
                <ul>
                    <li>✅ <?php _e('15 databázových tabulek vytvořeno', 'saw-lms'); ?></li>
                    <li>✅ <?php _e('Admin menu aktivní', 'saw-lms'); ?></li>
                    <li>✅ <?php _e('Upload složky vytvořeny a zabezpečeny', 'saw-lms'); ?></li>
                    <li>⏳ <?php _e('Custom Post Types - v další fázi', 'saw-lms'); ?></li>
                    <li>⏳ <?php _e('Course Builder - v další fázi', 'saw-lms'); ?></li>
                    <li>⏳ <?php _e('WooCommerce integrace - v další fázi', 'saw-lms'); ?></li>
                </ul>
            </div>
            
            <p>
                <strong><?php _e('Verze pluginu:', 'saw-lms'); ?></strong> <?php echo SAW_LMS_VERSION; ?><br>
                <strong><?php _e('Verze DB schématu:', 'saw-lms'); ?></strong> <?php echo get_option('saw_lms_db_version', 'N/A'); ?><br>
                <strong><?php _e('Instalováno:', 'saw-lms'); ?></strong> <?php echo get_option('saw_lms_installed_at', 'N/A'); ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Zobrazení Info stránky
     */
    public function display_info_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Nemáte oprávnění přistupovat k této stránce.', 'saw-lms'));
        }
        
        global $wpdb;
        
        // Načtení seznamu všech tabulek
        $tables = array(
            'saw_lms_enrollments',
            'saw_lms_progress',
            'saw_lms_quiz_attempts',
            'saw_lms_certificates',
            'saw_lms_points_ledger',
            'saw_lms_activity_log',
            'saw_lms_groups',
            'saw_lms_group_members',
            'saw_lms_custom_documents',
            'saw_lms_content_versions',
            'saw_lms_enrollment_content_versions',
            'saw_lms_content_changelog',
            'saw_lms_course_completion_snapshots',
            'saw_lms_course_schedules',
            'saw_lms_document_snapshots',
        );
        
        ?>
        <div class="wrap">
            <h1><?php _e('Plugin Info', 'saw-lms'); ?></h1>
            
            <h2><?php _e('📋 Databázové tabulky', 'saw-lms'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Tabulka', 'saw-lms'); ?></th>
                        <th><?php _e('Počet záznamů', 'saw-lms'); ?></th>
                        <th><?php _e('Status', 'saw-lms'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tables as $table): 
                        $table_name = $wpdb->prefix . $table;
                        $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
                        $count = $exists ? $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}") : 0;
                    ?>
                    <tr>
                        <td><code><?php echo esc_html($table_name); ?></code></td>
                        <td><?php echo $exists ? number_format($count) : 'N/A'; ?></td>
                        <td>
                            <?php if ($exists): ?>
                                <span style="color: #00a32a;">✓ <?php _e('Existuje', 'saw-lms'); ?></span>
                            <?php else: ?>
                                <span style="color: #d63638;">✗ <?php _e('Neexistuje', 'saw-lms'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <h2><?php _e('📁 Upload složky', 'saw-lms'); ?></h2>
            <?php
            $upload_dir = wp_upload_dir();
            $base_dir = $upload_dir['basedir'] . '/saw-lms';
            $directories = array(
                'Hlavní složka' => $base_dir,
                'Certifikáty' => $base_dir . '/certificates',
                'Skupinový obsah' => $base_dir . '/group-content',
                'Archivy' => $base_dir . '/archives',
                'Dočasné' => $base_dir . '/temp',
            );
            ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Složka', 'saw-lms'); ?></th>
                        <th><?php _e('Cesta', 'saw-lms'); ?></th>
                        <th><?php _e('Status', 'saw-lms'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($directories as $label => $dir): ?>
                    <tr>
                        <td><?php echo esc_html($label); ?></td>
                        <td><code><?php echo esc_html($dir); ?></code></td>
                        <td>
                            <?php if (is_dir($dir)): ?>
                                <span style="color: #00a32a;">✓ <?php _e('Existuje', 'saw-lms'); ?></span>
                            <?php else: ?>
                                <span style="color: #d63638;">✗ <?php _e('Neexistuje', 'saw-lms'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <h2><?php _e('⚙️ Plugin nastavení', 'saw-lms'); ?></h2>
            <?php
            $options = array(
                'saw_lms_version' => __('Verze', 'saw-lms'),
                'saw_lms_db_version' => __('Verze databáze', 'saw-lms'),
                'saw_lms_installed_at' => __('Instalováno', 'saw-lms'),
                'saw_lms_enable_certificates' => __('Certifikáty povoleny', 'saw-lms'),
                'saw_lms_enable_gamification' => __('Gamifikace povolena', 'saw-lms'),
                'saw_lms_points_per_lesson' => __('Body za lekci', 'saw-lms'),
                'saw_lms_points_per_quiz' => __('Body za kvíz', 'saw-lms'),
                'saw_lms_min_watch_percentage' => __('Min. % zhlédnutí videa', 'saw-lms'),
            );
            ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Nastavení', 'saw-lms'); ?></th>
                        <th><?php _e('Hodnota', 'saw-lms'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($options as $key => $label): ?>
                    <tr>
                        <td><?php echo esc_html($label); ?></td>
                        <td><code><?php echo esc_html(get_option($key, 'N/A')); ?></code></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}