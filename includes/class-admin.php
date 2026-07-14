<?php

if (!defined('ABSPATH')) {
    exit;
}

class Olama_Supervision_Admin extends Olama_School_Admin {
    public function __construct() {
        add_action('admin_menu', array($this, 'register_menu'), 30);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_init', array($this, 'redirect_legacy_page'), 1);
        add_action('admin_init', array($this, 'handle_lesson_planner_actions'));
    }

    public function register_menu() {
        $capability = Olama_School_Permissions::can('olama_access_supervision')
            ? 'olama_access_supervision'
            : 'olama_manage_lesson_planner';
        add_menu_page(
            __('Olama Supervision', 'olama-supervision'),
            __('Olama Supervision', 'olama-supervision'),
            $capability,
            'olama-supervision',
            array($this, 'render_page'),
            'dashicons-visibility',
            28
        );
        add_submenu_page(
            'olama-supervision',
            __('Academic Supervision', 'olama-supervision'),
            __('Academic Supervision', 'olama-supervision'),
            $capability,
            'olama-supervision',
            array($this, 'render_page')
        );
    }

    public function redirect_legacy_page() {
        if (empty($_GET['page']) || 'olama-school-supervision' !== sanitize_key(wp_unslash($_GET['page']))) {
            return;
        }
        $args = map_deep(wp_unslash($_GET), 'sanitize_text_field');
        $args['page'] = 'olama-supervision';
        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }

    public function enqueue_assets($hook) {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        if ('olama-supervision' !== $page) {
            return;
        }
        parent::enqueue_admin_assets('olama-school_supervision');
    }

    public function render_page() {
        $tabs_config = array(
            'plan_visit' => array('label' => __('Plan Visit', 'olama-supervision'), 'cap' => 'olama_manage_supervision_plan'),
            'complete_plan' => array('label' => __('Complete Plan', 'olama-supervision'), 'cap' => 'olama_manage_supervision_plan'),
            'assignments' => array('label' => __('Assign Supervisor', 'olama-supervision'), 'cap' => 'olama_manage_supervision_plan'),
            'reports' => array('label' => __('Reports', 'olama-supervision'), 'cap' => 'olama_view_supervision_reports'),
            'analytics' => array('label' => __('Analytics', 'olama-supervision'), 'cap' => 'olama_view_supervision_analytics'),
            'lesson_planner' => array('label' => Olama_School_Helpers::translate('Lesson Planner'), 'cap' => 'olama_manage_lesson_planner'),
        );
        $allowed_tabs = array();
        foreach ($tabs_config as $id => $tab) {
            if (Olama_School_Permissions::can($tab['cap'])) {
                $allowed_tabs[$id] = $tab;
            }
        }
        if (!$allowed_tabs) {
            wp_die(esc_html__('You do not have access to an Academic Supervision section.', 'olama-supervision'), '', array('response' => 403));
        }

        $active_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : array_key_first($allowed_tabs);
        if (!isset($allowed_tabs[$active_tab])) {
            $active_tab = array_key_first($allowed_tabs);
        }
        ?>
        <div class="wrap olama-school-wrap">
            <h1><?php esc_html_e('Academic Supervision', 'olama-supervision'); ?></h1>
            <h2 class="nav-tab-wrapper">
                <?php foreach ($allowed_tabs as $tab_slug => $tab_data): ?>
                    <a href="<?php echo esc_url(add_query_arg(array('page' => 'olama-supervision', 'tab' => $tab_slug), admin_url('admin.php'))); ?>"
                       class="nav-tab <?php echo $active_tab === $tab_slug ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($tab_data['label']); ?>
                    </a>
                <?php endforeach; ?>
            </h2>
            <div class="olama-tab-content" style="margin-top:20px;">
                <?php
                $views = array(
                    'plan_visit' => 'supervision-plan.php',
                    'complete_plan' => 'supervision-complete-plan.php',
                    'assignments' => 'supervision-assignments.php',
                    'reports' => 'supervision-reports.php',
                    'analytics' => 'supervision-analytics.php',
                    'lesson_planner' => 'lesson-planner.php',
                );
                include OLAMA_SCHOOL_PATH . 'includes/admin-views/' . $views[$active_tab];
                ?>
            </div>
        </div>
        <?php
    }
}

class Olama_Supervision_Lesson_Planner_Ajax extends Olama_School_Ajax_Handlers {
    public function __construct() {
        add_action('wp_ajax_olama_lp_get_units', array($this, 'lp_get_units'));
        add_action('wp_ajax_olama_lp_get_timeline_lessons', array($this, 'lp_get_timeline_lessons'));
        add_action('wp_ajax_olama_lp_get_teacher_subjects', array($this, 'lp_get_teacher_subjects'));
    }
}
