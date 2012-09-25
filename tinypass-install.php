<?php
register_activation_hook(__FILE__, 'tinypass_activate');
register_deactivation_hook(__FILE__, 'tinypass_deactivate');
register_uninstall_hook(__FILE__, 'tinypass_uninstall');

function tinypass_activate() {
	//global $wpdb;

	//tinypass_include();

	/*
	$tinypass_settings = array(
			'enabled' => 'on',
			'aid_sand' => 'W7JZEZFu2h', '', true,
			'secret_key_sand' => 'jeZC9ykDfvW6rXR8ZuO3EOkg9HaKFr90ERgEb3RW',
			'aid_prod' => 'GETKEY',
			'secret_key_prod' => 'Retreive your secret key from www.tinypass.com',
			'env' => 0,
			'inline' => 1,
			'access_message' => __('To continue, please purchase using TinyPass')
	);
	add_option("tinypass_settings", $tinypass_settings, 0, true);
	 */

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

	/*
	echo 'LSDJFLJSDFLJSLDFJSFD';
	exit;
	$wtf = get_plugin_data('tinypass.php');
	print_r($wtf);
	add_option('tinypass_version', 1, 0, true);
	die('new shit');
	 */
}

function tinypass_deactivate() {
	$tinypass_settings = get_option("tinypass_settings");
	$tinypass_settings['enabled'] == 'off';
	update_option("tinypass_settings", $tinypass_settings);
}

function tinypass_uninstall() {
	//global $wpdb;
	//$table_name = $wpdb->prefix . 'tinypass_ref';
	//$wpdb->query("drop table $table_name ");
	$wpdb->query("delete from $wpdb->postmeta where meta_key = 'tinypass'");
	$wpdb->query("delete from $wpdb->options where option_name like 'tinypass%'");
}

?>