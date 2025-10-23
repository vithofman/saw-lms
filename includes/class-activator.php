<?php
/**
 * Aktivátor pluginu
 * Vytvoří všechny databázové tabulky při aktivaci
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAW_LMS_Activator {
    
    /**
     * Spustí se při aktivaci pluginu
     */
    public static function activate() {
        global $wpdb;
        
        // Požadujeme charset a collation
        $charset_collate = $wpdb->get_charset_collate();
        
        // Pole pro všechny SQL dotazy
        $sql = array();
        
        // ==================================================
        // 1. ENROLLMENTS - Zápisy do kurzů
        // ==================================================
        $table_name = $wpdb->prefix . 'saw_lms_enrollments';
        $sql[] = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            course_id bigint(20) UNSIGNED NOT NULL,
            group_id bigint(20) UNSIGNED DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'enrolled',
            attempt_number int(11) NOT NULL DEFAULT 1,
            source varchar(50) NOT NULL DEFAULT 'manual',
            source_id bigint(20) UNSIGNED DEFAULT NULL,
            started_at datetime DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            expires_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_course_id (course_id),
            KEY idx_group_id (group_id),
            KEY idx_status (status),
            KEY idx_user_course (user_id, course_id),
            KEY idx_source (source, source_id)
        ) {$charset_collate};";
        
        // ==================================================
        // 2. PROGRESS - Progress v lekcích
        // ==================================================
        $table_name = $wpdb->prefix . 'saw_lms_progress';
        $sql[] = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            enrollment_id bigint(20) UNSIGNED NOT NULL,
            lesson_id bigint(20) UNSIGNED NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'not_started',
            completion_percentage int(3) NOT NULL DEFAULT 0,
            time_spent int(11) NOT NULL DEFAULT 0,
            video_watched_seconds int(11) DEFAULT NULL,
            video_duration int(11) DEFAULT NULL,
            last_position int(11) DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_enrollment_id (enrollment_id),
            KEY idx_lesson_id (lesson_id),
            KEY idx_status (status),
            UNIQUE KEY idx_enrollment_lesson (enrollment_id, lesson_id)
        ) {$charset_collate};";
        
        // ==================================================
        // 3. QUIZ ATTEMPTS - Pokusy o kvízy
        // ==================================================
        $table_name = $wpdb->prefix . 'saw_lms_quiz_attempts';
        $sql[] = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            enrollment_id bigint(20) UNSIGNED NOT NULL,
            quiz_id bigint(20) UNSIGNED NOT NULL,
            attempt_number int(11) NOT NULL DEFAULT 1,
            answers_json longtext NOT NULL,
            score decimal(5,2) DEFAULT NULL,
            passed tinyint(1) NOT NULL DEFAULT 0,
            time_taken int(11) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent varchar(255) DEFAULT NULL,
            tab_switches int(11) NOT NULL DEFAULT 0,
            flagged tinyint(1) NOT NULL DEFAULT 0,
            reviewed_at datetime DEFAULT NULL,
            reviewed_by bigint(20) UNSIGNED DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_enrollment_id (enrollment_id),
            KEY idx_quiz_id (quiz_id),
            KEY idx_passed (passed),
            KEY idx_flagged (flagged),
            KEY idx_enrollment_quiz (enrollment_id, quiz_id)
        ) {$charset_collate};";
        
        // ==================================================
        // 4. CERTIFICATES - Certifikáty
        // ==================================================
        $table_name = $wpdb->prefix . 'saw_lms_certificates';
        $sql[] = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            course_id bigint(20) UNSIGNED NOT NULL,
            enrollment_id bigint(20) UNSIGNED NOT NULL,
            code varchar(50) NOT NULL UNIQUE,
            pdf_url varchar(500) DEFAULT NULL,
            issued_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime DEFAULT NULL,
            revoked_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_course_id (course_id),
            KEY idx_code (code),
            KEY idx_enrollment_id (enrollment_id)
        ) {$charset_collate};";
        
        // ==================================================
        // 5. POINTS LEDGER - Bodový systém
        // ==================================================
        $table_name = $wpdb->prefix . 'saw_lms_points_ledger';
        $sql[] = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            amount int(11) NOT NULL,
            balance int(11) NOT NULL DEFAULT 0,
            reason varchar(255) NOT NULL,
            reference_type varchar(50) DEFAULT NULL,
            reference_id bigint(20) UNSIGNED DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_reference (reference_type, reference_id),
            KEY idx_created_at (created_at)
        ) {$charset_collate};";
        
        // ==================================================
        // 6. ACTIVITY LOG - Audit trail
        // ==================================================
        $table_name = $wpdb->prefix . 'saw_lms_activity_log';
        $sql[] = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            action varchar(100) NOT NULL,
            entity_type varchar(50) NOT NULL,
            entity_id bigint(20) UNSIGNED NOT NULL,
            metadata_json text DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent varchar(255) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_action (action),
            KEY idx_entity (entity_type, entity_id),
            KEY idx_created_at (created_at)
        ) {$charset_collate};";
        
        // ==================================================
        // 7. GROUPS - Skupinové licence
        // ==================================================
        $table_name = $wpdb->prefix . 'saw_lms_groups';
        $sql[] = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            admin_user_id bigint(20) UNSIGNED NOT NULL,
            course_id bigint(20) UNSIGNED NOT NULL,
            total_seats int(11) NOT NULL DEFAULT 0,
            used_seats int(11) NOT NULL DEFAULT 0,
            order_id bigint(20) UNSIGNED DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            expires_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_admin_user_id (admin_user_id),
            KEY idx_course_id (course_id),
            KEY idx_status (status),
            KEY idx_order_id (order_id)
        ) {$charset_collate};";
        
        // ==================================================
        // 8. GROUP MEMBERS - Členové skupin
        // ==================================================
        $table_name = $wpdb->prefix . 'saw_lms_group_members';
        $sql[] = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            group_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            role varchar(20) NOT NULL DEFAULT 'member',
            added_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            removed_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_group_id (group_id),
            KEY idx_user_id (user_id),
            UNIQUE KEY idx_group_user (group_id, user_id)
        ) {$charset_collate};";
        
        // ==================================================
        // 9. CUSTOM DOCUMENTS - Vlastní dokumenty pro skupiny
        // ==================================================
        $table_name = $wpdb->prefix . 'saw_lms_custom_documents';
        $sql[] = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            group_id bigint(20) UNSIGNED NOT NULL,
            lesson_id bigint(20) UNSIGNED NOT NULL,
            file_name varchar(255) NOT NULL,
            file_path varchar(500) NOT NULL,
            file_size int(11) NOT NULL DEFAULT 0,
            file_type varchar(50) NOT NULL,
            file_hash varchar(64) NOT NULL,
            uploaded_by bigint(20) UNSIGNED NOT NULL,
            uploaded_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_group_id (group_id),
            KEY idx_lesson_id (lesson_id),
            KEY idx_file_hash (file_hash)
        ) {$charset_collate};";
        
        // ==================================================
        // 10. CONTENT VERSIONS - Verze obsahu
        // ==================================================
        $table_name = $wpdb->prefix . 'saw_lms_content_versions';
        $sql[] = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            entity_type varchar(50) NOT NULL,
            entity_id bigint(20) UNSIGNED NOT NULL,
            version_number varchar(20) NOT NULL,
            snapshot_json longtext NOT NULL,
            content_hash varchar(64) NOT NULL,
            created_by bigint(20) UNSIGNED NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_entity (entity_type, entity_id),
            KEY idx_version_number (version_number),
            KEY idx_content_hash (content_hash)
        ) {$charset_collate};";
        
        // ==================================================
        // 11. ENROLLMENT CONTENT VERSIONS - Propojení enrollment s verzemi
        // ==================================================
        $table_name = $wpdb->prefix . 'saw_lms_enrollment_content_versions';
        $sql[] = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            enrollment_id bigint(20) UNSIGNED NOT NULL,
            entity_type varchar(50) NOT NULL,
            entity_id bigint(20) UNSIGNED NOT NULL,
            version_id bigint(20) UNSIGNED NOT NULL,
            viewed_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_enrollment_id (enrollment_id),
            KEY idx_entity (entity_type, entity_id),
            KEY idx_version_id (version_id)
        ) {$charset_collate};";
        
        // ==================================================
        // 12. CONTENT CHANGELOG - Log změn obsahu
        // ==================================================
        $table_name = $wpdb->prefix . 'saw_lms_content_changelog';
        $sql[] = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            version_id bigint(20) UNSIGNED NOT NULL,
            change_summary text NOT NULL,
            changed_by bigint(20) UNSIGNED NOT NULL,
            changed_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_version_id (version_id),
            KEY idx_changed_at (changed_at)
        ) {$charset_collate};";
        
        // ==================================================
        // 13. COURSE COMPLETION SNAPSHOTS - Snímky při dokončení
        // ==================================================
        $table_name = $wpdb->prefix . 'saw_lms_course_completion_snapshots';
        $sql[] = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            enrollment_id bigint(20) UNSIGNED NOT NULL,
            snapshot_json longtext NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_enrollment_id (enrollment_id)
        ) {$charset_collate};";
        
        // ==================================================
        // 14. COURSE SCHEDULES - Periodické opakování
        // ==================================================
        $table_name = $wpdb->prefix . 'saw_lms_course_schedules';
        $sql[] = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            course_id bigint(20) UNSIGNED NOT NULL,
            enrollment_id bigint(20) UNSIGNED NOT NULL,
            repeat_period_months int(11) NOT NULL DEFAULT 0,
            last_completed_at datetime NOT NULL,
            next_due_date datetime NOT NULL,
            reminder_sent_at datetime DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_course_id (course_id),
            KEY idx_next_due_date (next_due_date),
            KEY idx_status (status)
        ) {$charset_collate};";
        
        // ==================================================
        // 15. DOCUMENT SNAPSHOTS - Archiv dokumentů
        // ==================================================
        $table_name = $wpdb->prefix . 'saw_lms_document_snapshots';
        $sql[] = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            enrollment_id bigint(20) UNSIGNED NOT NULL,
            document_id bigint(20) UNSIGNED NOT NULL,
            file_hash varchar(64) NOT NULL,
            snapshot_path varchar(500) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_enrollment_id (enrollment_id),
            KEY idx_document_id (document_id),
            KEY idx_file_hash (file_hash)
        ) {$charset_collate};";
        
        // ==================================================
        // SPUŠTĚNÍ VŠECH SQL DOTAZŮ
        // ==================================================
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        foreach ($sql as $query) {
            dbDelta($query);
        }
        
        // Uložení verze databáze
        update_option('saw_lms_db_version', SAW_LMS_VERSION);
        
        // Nastavení default options
        self::set_default_options();
        
        // Vytvoření upload složek
        self::create_upload_directories();
        
        // Transient pro zobrazení notice po aktivaci
        set_transient('saw_lms_activation_notice', true, 60);
        
        // Log pro debug
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SAW LMS: Plugin aktivován, vytvořeno 15 tabulek');
        }
    }
    
    /**
     * Nastavení výchozích hodnot v options
     */
    private static function set_default_options() {
        $defaults = array(
            'saw_lms_version' => SAW_LMS_VERSION,
            'saw_lms_installed_at' => current_time('mysql'),
            'saw_lms_enable_certificates' => 1,
            'saw_lms_enable_gamification' => 1,
            'saw_lms_enable_versioning' => 1,
            'saw_lms_points_per_lesson' => 10,
            'saw_lms_points_per_quiz' => 20,
            'saw_lms_points_per_course' => 100,
            'saw_lms_min_watch_percentage' => 80,
            'saw_lms_tracking_interval' => 10,
            'saw_lms_default_passing_score' => 70,
        );
        
        foreach ($defaults as $key => $value) {
            if (false === get_option($key)) {
                add_option($key, $value);
            }
        }
    }
    
    /**
     * Vytvoření upload složek s ochranou
     */
    private static function create_upload_directories() {
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'] . '/saw-lms';
        
        $directories = array(
            $base_dir,
            $base_dir . '/certificates',
            $base_dir . '/group-content',
            $base_dir . '/archives',
            $base_dir . '/temp',
        );
        
        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
            }
            
            // Vytvoření .htaccess pro ochranu
            $htaccess_file = $dir . '/.htaccess';
            if (!file_exists($htaccess_file)) {
                $htaccess_content = "deny from all\n";
                file_put_contents($htaccess_file, $htaccess_content);
            }
            
            // Vytvoření index.php (silence is golden)
            $index_file = $dir . '/index.php';
            if (!file_exists($index_file)) {
                file_put_contents($index_file, "<?php\n// Silence is golden\n");
            }
        }
    }
}