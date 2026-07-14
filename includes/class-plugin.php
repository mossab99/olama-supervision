<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Olama_Supervision_Plugin {
    private static $instance = null;
    private $available = false;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function activate() {
        if (!class_exists('Olama_School_Admin') || !class_exists('Olama_School_Supervision_Ajax_Handlers')) {
            deactivate_plugins(plugin_basename(OLAMA_SUPERVISION_FILE));
            wp_die(
                esc_html__('Olama Supervision requires Olama School and Olama Student Evaluation to be active.', 'olama-supervision'),
                esc_html__('Plugin dependency missing', 'olama-supervision'),
                array('back_link' => true)
            );
        }

        Olama_Supervision_DB::install();
        self::add_capabilities();
        update_option('olama_supervision_db_version', OLAMA_SUPERVISION_VERSION);
    }

    private function __construct() {
        load_plugin_textdomain('olama-supervision', false, dirname(plugin_basename(OLAMA_SUPERVISION_FILE)) . '/languages');
        add_action('admin_notices', array($this, 'dependency_notice'));
        add_filter('olama_core_capability_groups', array($this, 'register_capability_group'), 35);
        add_filter('olama_dashboard_cards', array($this, 'register_hub_card'), 20);

        $this->available = $this->dependencies_available();
        if (!$this->available) {
            return;
        }

        require_once OLAMA_SUPERVISION_PATH . 'includes/class-admin.php';
        if (is_admin()) {
            new Olama_Supervision_Admin();
            new Olama_Supervision_Lesson_Planner_Ajax();
            new Olama_School_Supervision_Ajax_Handlers();
            add_action('admin_init', array($this, 'maybe_update_schema'), 5);
        }
    }

    private function dependencies_available() {
        return defined('OLAMA_SCHOOL_FILE')
            && defined('OLAMA_STUDENT_EVALUATION_FILE')
            && class_exists('Olama_School_Admin')
            && class_exists('Olama_School_Ajax_Handlers')
            && class_exists('Olama_School_Supervision_Ajax_Handlers')
            && class_exists('Olama_School_Lesson_Planner')
            && class_exists('Olama_School_EV_Record');
    }

    public function maybe_update_schema() {
        if (get_option('olama_supervision_db_version') === OLAMA_SUPERVISION_VERSION) {
            return;
        }
        Olama_Supervision_DB::install();
        self::add_capabilities();
        update_option('olama_supervision_db_version', OLAMA_SUPERVISION_VERSION);
    }

    public static function add_capabilities() {
        $caps = array(
            'olama_access_supervision',
            'olama_manage_supervision_plan',
            'olama_view_supervision_reports',
            'olama_view_supervision_analytics',
            'olama_manage_lesson_planner',
        );
        foreach (array('administrator', 'editor', 'supervisor') as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                foreach ($caps as $cap) {
                    $role->add_cap($cap);
                }
            }
        }
        foreach (array('author', 'teacher', 'assistant') as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                $role->add_cap('olama_manage_lesson_planner');
            }
        }
    }

    public function register_capability_group($groups) {
        $groups['supervision'] = array(
            'label' => __('Olama Supervision', 'olama-supervision'),
            'caps' => array(
                'olama_access_supervision' => __('Access Academic Supervision', 'olama-supervision'),
                'olama_manage_supervision_plan' => __('Manage Visits and Assignments', 'olama-supervision'),
                'olama_view_supervision_reports' => __('View Supervision Reports', 'olama-supervision'),
                'olama_view_supervision_analytics' => __('View Supervision Analytics', 'olama-supervision'),
                'olama_manage_lesson_planner' => __('Manage Lesson Planner', 'olama-supervision'),
            ),
        );
        return $groups;
    }

    public function register_hub_card($cards) {
        if (!$this->available) {
            return $cards;
        }
        foreach ($cards as &$card) {
            if (($card['id'] ?? '') === 'olama-school' && !empty($card['submenus'])) {
                $card['submenus'] = array_values(array_filter($card['submenus'], function ($submenu) {
                    return ($submenu['id'] ?? '') !== 'school.supervision';
                }));
            }
        }
        unset($card);

        $access_capability = Olama_School_Permissions::can('olama_access_supervision')
            ? 'olama_access_supervision'
            : 'olama_manage_lesson_planner';
        $cards[] = array(
            'id' => 'olama-supervision',
            'label' => __('Olama Supervision', 'olama-supervision'),
            'description' => __('Academic visits, supervisor assignments, reports, analytics, and lesson planning.', 'olama-supervision'),
            'icon' => 'dashicons-visibility',
            'accent' => '#4f46e5',
            'accent_rgb' => '79,70,229',
            'active' => true,
            'capability' => $access_capability,
            'primary_url' => admin_url('admin.php?page=olama-supervision'),
            'submenus' => array(
                array('id' => 'supervision.plan', 'label' => __('Plan Visit', 'olama-supervision'), 'icon' => 'dashicons-calendar-alt', 'url' => admin_url('admin.php?page=olama-supervision&tab=plan_visit'), 'capability' => 'olama_manage_supervision_plan', 'color' => '#4f46e5'),
                array('id' => 'supervision.complete', 'label' => __('Complete Plan', 'olama-supervision'), 'icon' => 'dashicons-yes-alt', 'url' => admin_url('admin.php?page=olama-supervision&tab=complete_plan'), 'capability' => 'olama_manage_supervision_plan', 'color' => '#4f46e5'),
                array('id' => 'supervision.assignments', 'label' => __('Assign Supervisor', 'olama-supervision'), 'icon' => 'dashicons-groups', 'url' => admin_url('admin.php?page=olama-supervision&tab=assignments'), 'capability' => 'olama_manage_supervision_plan', 'color' => '#4f46e5'),
                array('id' => 'supervision.reports', 'label' => __('Reports', 'olama-supervision'), 'icon' => 'dashicons-chart-bar', 'url' => admin_url('admin.php?page=olama-supervision&tab=reports'), 'capability' => 'olama_view_supervision_reports', 'color' => '#4f46e5'),
                array('id' => 'supervision.analytics', 'label' => __('Analytics', 'olama-supervision'), 'icon' => 'dashicons-chart-area', 'url' => admin_url('admin.php?page=olama-supervision&tab=analytics'), 'capability' => 'olama_view_supervision_analytics', 'color' => '#4f46e5'),
                array('id' => 'supervision.lesson-planner', 'label' => __('Lesson Planner', 'olama-supervision'), 'icon' => 'dashicons-welcome-write-blog', 'url' => admin_url('admin.php?page=olama-supervision&tab=lesson_planner'), 'capability' => 'olama_manage_lesson_planner', 'color' => '#4f46e5'),
            ),
        );
        return $cards;
    }

    public function dependency_notice() {
        if ($this->available || !current_user_can('activate_plugins')) {
            return;
        }
        echo '<div class="notice notice-error"><p>'
            . esc_html__('Olama Supervision is inactive because Olama School or Olama Student Evaluation is not active.', 'olama-supervision')
            . '</p></div>';
    }
}
