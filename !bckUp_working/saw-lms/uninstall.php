<?php
/**
 * Uninstall script - spustí se pouze když uživatel smaže plugin
 */

// Zabránit přímému přístupu
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Smazání všech custom tabulek
$tables = array(
    $wpdb->prefix . 'saw_lms_enrollments',
    $wpdb->prefix . 'saw_lms_progress',
    $wpdb->prefix . 'saw_lms_quiz_attempts',
    $wpdb->prefix . 'saw_lms_certificates',
    $wpdb->prefix . 'saw_lms_points_ledger',
    $wpdb->prefix . 'saw_lms_activity_log',
    $wpdb->prefix . 'saw_lms_groups',
    $wpdb->prefix . 'saw_lms_group_members',
    $wpdb->prefix . 'saw_lms_custom_documents',
    $wpdb->prefix . 'saw_lms_content_versions',
    $wpdb->prefix . 'saw_lms_enrollment_content_versions',
    $wpdb->prefix . 'saw_lms_content_changelog',
    $wpdb->prefix . 'saw_lms_course_completion_snapshots',
    $wpdb->prefix . 'saw_lms_course_schedules',
    $wpdb->prefix . 'saw_lms_document_snapshots',
);

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$table}");
}

// Smazání všech plugin options
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'saw_lms_%'");

// Smazání všech post meta souvisejících s pluginem
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_saw_lms_%'");

// Smazání všech user meta souvisejících s pluginem
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '_saw_lms_%'");

// Vyčištění cache
wp_cache_flush();

// Log pro debug (jen pokud je WP_DEBUG true)
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('SAW LMS: Plugin byl kompletně odstraněn včetně všech dat');
}