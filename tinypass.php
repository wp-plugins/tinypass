<?php

/*
  Plugin Name: TinyPass
  Plugin URI: http://www.tinypass.com
  Description: Tinypass is the best way to charge for access to content on your WordPress site.  1) Go to http://developer.tinypass.com/main/wordpress and follow the installation instructions to create a Tinypass publisher account and configure the Tinypass plugin for your WordPress site
  Author: Tinypass
  Version: 3.0.0
  Author URI: http://www.tinypass.com
 */

define('TINYPASS_PLUGIN_PATH', plugin_dir_path(__FILE__) . "/tinypass.php");

register_activation_hook(__FILE__, 'tinypass_activate');
register_deactivation_hook(__FILE__, 'tinypass_deactivate');
register_uninstall_hook(__FILE__, 'tinypass_uninstall');

$tinypass_current_version = get_option('tinypass_version');

if ($tinypass_current_version == '' || $tinypass_current_version < '3.0.0' || get_option('tinypass_legacy') == 1) {
	include_once dirname(__FILE__) . '/legacy/legacy.php';
} else {
	include_once dirname(__FILE__) . '/jslite/jslite.php';
}
?>