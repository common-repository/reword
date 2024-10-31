<?php

/**
 * Plugin Name:  ReWord
 * Plugin URI:   http://reword.000webhostapp.com/wordpress
 * Description:  This plugin allows readers to suggest fixes for content mistakes in your site. Intuitive frontend UI lets users report mistakes and send them to Administrator. Just mark mistake text, click on “R” icon, add your fix and send it. The reports admin page displays all reported mistakes, and lets admin fix them, or ignore them. Admin can also set the number of alerts before showing a report, to ensure accurate reports and real issues detection.
 * Version:      3.0
 * Author:       TiomKing
 * Author URI:   https://profiles.wordpress.org/tiomking
 * License:      GPLv2 or later
 *
 * Reword is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Reword is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 */

// Reword log level defines
define('REWORD_ERR', 'Reword ERROR');
define('REWORD_NOTICE', 'Reword NOTICE');

// Reword plugin file path (reword/reword.php)
define('REWORD_PLUGIN_BASENAME', plugin_basename(__FILE__));
// Reword plugin name (reword)
define('REWORD_PLUGIN_NAME', trim(dirname(REWORD_PLUGIN_BASENAME), '/'));
// Full path to reword plugin directory (/host/../wp-content/plugins/reword)
define('REWORD_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . REWORD_PLUGIN_NAME);
// Full path to reword plugin file (/host/../wp-content/plugins/reword/reword.php)
define('REWORD_PLUGIN_PATH', WP_PLUGIN_DIR . '/' . REWORD_PLUGIN_BASENAME);
// Full path to reword admin directory (/host/../wp-content/plugins/reword/admin)
define('REWORD_ADMIN_DIR', REWORD_PLUGIN_DIR . '/admin');
// Full path to reword public directory (/host/../wp-content/plugins/reword/public)
define('REWORD_PUBLIC_DIR', REWORD_PLUGIN_DIR . '/public');
// Full path to reword classes directory (/host/../wp-content/plugins/reword/class)
define('REWORD_CLASS_DIR', REWORD_PLUGIN_DIR . '/class');
// URL of reword plugin (http://../wp-content/plugins/reword)
define('REWORD_PLUGIN_URL', WP_PLUGIN_URL . '/' . REWORD_PLUGIN_NAME);
// URL to reword admin directory (http://../wp-content/plugins/reword/admin)
define('REWORD_ADMIN_URL', REWORD_PLUGIN_URL . '/admin');
// URL to reword public directory (http://../wp-content/plugins/reword/public)
define('REWORD_PUBLIC_URL', REWORD_PLUGIN_URL . '/public');

// Reword plugin version (as in header above)
if (is_admin()) {
    // Only admin can get plugin data
    require_once(ABSPATH . "wp-admin/includes/plugin.php");
    $reword_ver = get_plugin_data(REWORD_PLUGIN_PATH)['Version'];
} else {
    // Last updated version
    $reword_ver = get_option('reword_plugin_version');
    if (empty($reword_ver)) {
        // No version setting, set default
        $reword_ver = '1.0';
    }
}
define('REWORD_PLUGIN_VERSION', $reword_ver);

// Reword SQL database table name
global $wpdb;
define('REWORD_DB_NAME', $wpdb->prefix . REWORD_PLUGIN_NAME);

// Reword options and default values
global $reword_options;
$reword_options = array(
    'reword_notice_banner'  => 'false',
    'reword_banner_pos'     => 'bottom',
    'reword_icon_pos'       => 'reword-icon-top reword-icon-left',
    'reword_reports_min'    => 1,
    'reword_email_new'      => array(),
    'reword_access_cap'     => 'manage_options',
    'reword_send_stats'     => 'false',
    'reword_plugin_version' => REWORD_PLUGIN_VERSION,
);

// Reword class
if (!class_exists('Reword_Plugin')) {
    require_once(REWORD_CLASS_DIR . '/class-reword-plugin.php');
}

// Load reword
$reword_plugin = new Reword_Plugin;
$reword_plugin->reword_init();
