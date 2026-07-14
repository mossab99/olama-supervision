<?php

if (!defined('ABSPATH')) {
    exit;
}

class Olama_Supervision_DB {
    public static function install() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset_collate = $wpdb->get_charset_collate();

        dbDelta("CREATE TABLE {$wpdb->prefix}olama_lesson_plans (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            academic_year_id mediumint(9) NOT NULL,
            semester_id mediumint(9) NOT NULL,
            teacher_id bigint(20) UNSIGNED NOT NULL,
            subject_id mediumint(9) NOT NULL,
            grade_id mediumint(9) NOT NULL,
            section_id mediumint(9) NOT NULL,
            unit_id mediumint(9) DEFAULT NULL,
            lesson_id mediumint(9) DEFAULT NULL,
            lesson_title text NOT NULL,
            start_date date DEFAULT NULL,
            end_date date DEFAULT NULL,
            number_of_classes tinyint(4) DEFAULT 1 NOT NULL,
            period_duration tinyint(4) DEFAULT 45 NOT NULL,
            learning_outcomes longtext DEFAULT NULL,
            prior_learning text DEFAULT NULL,
            stages longtext DEFAULT NULL,
            teaching_strategies_used longtext DEFAULT NULL,
            assessment_strategies_used longtext DEFAULT NULL,
            assessment_tools_used longtext DEFAULT NULL,
            resources text DEFAULT NULL,
            self_reflection text DEFAULT NULL,
            homework text DEFAULT NULL,
            compliance_score tinyint(4) DEFAULT 0 NOT NULL,
            status varchar(20) DEFAULT 'draft' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY year_semester (academic_year_id,semester_id),
            KEY teacher_id (teacher_id),
            KEY section_subject_date (section_id,subject_id,start_date)
        ) {$charset_collate};");

        dbDelta("CREATE TABLE {$wpdb->prefix}olama_supervisor_visits (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            schedule_id mediumint(9) NOT NULL,
            supervisor_id bigint(20) UNSIGNED NOT NULL,
            unit_id mediumint(9) DEFAULT NULL,
            lesson_id mediumint(9) DEFAULT NULL,
            visit_date date NOT NULL,
            status enum('planned','completed','approved') DEFAULT 'planned' NOT NULL,
            final_score decimal(5,2) DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY schedule_id (schedule_id),
            KEY visit_date (visit_date),
            KEY supervisor_id (supervisor_id)
        ) {$charset_collate};");

        dbDelta("CREATE TABLE {$wpdb->prefix}olama_supervisor_assignments (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            academic_year_id mediumint(9) NOT NULL,
            semester_id mediumint(9) NOT NULL,
            supervisor_id bigint(20) UNSIGNED NOT NULL,
            grade_id mediumint(9) NOT NULL,
            subject_id mediumint(9) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY year_semester (academic_year_id,semester_id),
            KEY supervisor_id (supervisor_id),
            KEY grade_subject (grade_id,subject_id)
        ) {$charset_collate};");
    }

    public static function table_names() {
        return array('olama_lesson_plans', 'olama_supervisor_visits', 'olama_supervisor_assignments');
    }
}
