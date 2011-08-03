<?php
function tinypass_install() {
	global $wpdb;

	$tinypass_settings = array(
					'enabled'=> 'on',
					'aid_sand'=> 'QdXYalSxyk', '', true,
					'secret_key_sand' => 'zXKpS9HhU9GdOn2jEH0kmzKW6jN4phrSbQ56ip9r',
					'aid_prod' => 'GETKEY',
					'secret_key_prod' => 'Retreive your secret key from www.tinypass.com',
					'env' => 0,
					'inline' => 1,
					'access_message' => __('To continue, please purchase using TinyPass')
	);
	add_option("tinypass_settings", $tinypass_settings, 0, true);
	
	$table_name = $wpdb->prefix . 'tinypass_ref';

	$sql = "CREATE TABLE $table_name (
						`term_id` BIGINT(20) UNSIGNED NOT NULL,
						`type` TINYINT UNSIGNED NOT NULL DEFAULT 0,
						`data` LONGTEXT,
						INDEX `tinypass_ref_index`(`term_id`)
					)";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
}

function tinypass_uninstall() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'tinypass_ref';
	$wpdb->query("drop table $table_name ");
	$wpdb->query("delete from $wpdb->postmeta where meta_key = 'tinypass'");
	$wpdb->query("delete from $wpdb->options where option_name like 'tinypass%'");
}
?>
