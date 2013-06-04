<?php

/*
  Plugin Name: TinyPass
  Plugin URI: http://www.tinypass.com
  Description: Tinypass is the best way to charge for access to content on your WordPress site.  To get started: 1) Click the "Activate" link to the left of this description, 2) Go to http://developer.tinypass.com/main/wordpress and follow the installation instructions to create a free Tinypass publisher account and configure the Tinypass plugin for your WordPress site
  Author: Tinypass
  Version: 3.0.0
  Author URI: http://www.tinypass.com
 */

function tinypass_include_legacy() {
	include_once dirname(__FILE__) . '/legacy/legacy.php';
}

function tinypass_include_jslite() {
	include_once dirname(__FILE__) . '/jslite/tinypass.php';
}

$current = get_option('tinypass_version');

if (1==1 && $current < '3.0.0' || get_option('tinypass_legacy') == 1) {
	tinypass_include_legacy();
} else {
	tinypass_include_jslite();
}
?>