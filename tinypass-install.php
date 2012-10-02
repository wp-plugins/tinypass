<?php

//register_activation_hook(__FILE__, 'tinypass_activate');
//register_deactivation_hook(__FILE__, 'tinypass_deactivate');
//register_uninstall_hook(__FILE__, 'tinypass_uninstall');

function tinypass_activate() {
	/*
	  $table_name = $wpdb->prefix . 'tinypass_ref';

	  $sql = "CREATE TABLE $table_name (
	  `term_id` BIGINT(20) UNSIGNED NOT NULL,
	  `type` TINYINT UNSIGNED NOT NULL DEFAULT 0,
	  `data` LONGTEXT,
	  INDEX `tinypass_ref_index`(`term_id`)
	  )";
	 */
	//require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	//dbDelta($sql);

	$error = '';
	if (!extension_loaded('mbstring'))
		$error .= "&nbsp;&nbsp;&nbsp;<a href=\"http://php.net/manual/en/ref.mbstring.php\">mbstring php module</a> is required for Tinypass<br>";
	if (!extension_loaded('mcrypt'))
		$error .= "&nbsp;&nbsp;&nbsp;<a href=\"http://php.net/manual/en/book.mcrypt.php\">mcrypt php module</a> is required for Tinypass<br>";

	if (version_compare(PHP_VERSION, '5.2.0') < 0) {
		$error .= "&nbsp;&nbsp;&nbsp;Requires PHP 5.2+";
	}

	if ($error)
		die('TinyPass could not be enabled<br>' . $error);

	$old = get_option("tinypass_setting");
	if ($old && count($old)) {
		$message = "Upgrading from Tinypass version 1.x to 2.x is currently restricted.  <br><br>Please contact support@tinypass.com for migration instructions from 1.x to 2.x";
		$message .= "<br><br>You can restore your previous version by manually downloading latest 1.4.x plugin at http://wordpress.org/extend/plugins/tinypass/developers";
		$message .= "<br><br>You can manually upgrade by uninstalling the TinyPass plugin and then performing a brand new install.  All your existing settings will be lost!!";
		die($message);
	}

	$data = get_plugin_data(plugin_dir_path(__FILE__) . "/tinypass.php");
	$version = $data['Version'];
	update_option('tinypass_version', $version);
}

function tinypass_deactivate() {
	$storage = new TPStorage();
	$ss = $storage->getSiteSettings();
	$ss->setEnabled(0);
	$storage->saveSiteSettings($ss);
}

function tinypass_uninstall() {
}

?>