<?php
/**
 * Plugin Name: Olama Supervision
 * Description: Standalone academic supervision, visit planning, reporting, analytics, and lesson planning for Olama School.
 * Version: 1.0.0
 * Author: Olama
 * Text Domain: olama-supervision
 * Requires Plugins: olama-school, olama-student-evaluation
 */

if (!defined('ABSPATH')) {
    exit;
}

define('OLAMA_SUPERVISION_VERSION', '1.0.0');
define('OLAMA_SUPERVISION_FILE', __FILE__);
define('OLAMA_SUPERVISION_PATH', plugin_dir_path(__FILE__));
define('OLAMA_SUPERVISION_URL', plugin_dir_url(__FILE__));

require_once OLAMA_SUPERVISION_PATH . 'includes/class-db.php';
require_once OLAMA_SUPERVISION_PATH . 'includes/class-plugin.php';

register_activation_hook(__FILE__, array('Olama_Supervision_Plugin', 'activate'));
add_action('plugins_loaded', array('Olama_Supervision_Plugin', 'instance'), 25);
