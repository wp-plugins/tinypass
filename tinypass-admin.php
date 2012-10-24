<?php

define('TINYPASS_FAVICON', 'http://www.tinypass.com/favicon.ico');

add_action("admin_menu", 'tinypass_add_admin_pages');

function tinypass_add_admin_pages() {
	add_menu_page('Tinypass', 'Tinypass', 'edit_plugins', 'TinypassSlug', 'tinypass_paywalls_list', TINYPASS_FAVICON);
	add_submenu_page('TinypassSlug', 'Paywalls', 'Paywalls', 'edit_plugins', 'TinyPassSlug', 'tinypass_paywalls_list');
//	add_submenu_page('Tinypass', 'Pay per Post', 'Pay per Post', 'edit_plugins', 'TinyPassPPVSettings', 'tinypass_ppv_settings');
//	add_submenu_page('Tinypass', 'Settings', 'Settings', 'edit_plugins', 'TinyPassSiteSettings', 'tinypass_site_settings');
	add_submenu_page('Tinypass', 'Edit Paywall', '', 'edit_plugins', 'TinyPassEditPaywall', 'tinypass_mode_settings');

	tinypass_include();

	wp_enqueue_script('suggest');

	include_once (dirname(__FILE__) . '/tinypass-mode-settings.php');
	include_once (dirname(__FILE__) . '/tinypass-ppp-settings.php');
	include_once (dirname(__FILE__) . '/tinypass-site-settings.php');
	include_once (dirname(__FILE__) . '/tinypass-paywalls.php');
}

/* Post/Page edit forms meta boxes */
add_action('add_meta_boxes', 'tinypass_add_meta_boxes');

function tinypass_add_meta_boxes() {

  $ss = tinypass_load_settings();

	if (!$ss->isPPVEnabled()) {
		return;
	}

	add_meta_box(
					'tinypass_post_options', '<img src="' . TINYPASS_FAVICON . '">&nbsp;' . __('Tinypass Options'), 'tinypass_meta_box_display', 'post', 'side'
	);
	add_meta_box(
					'tinypass_post_options', '<img src="' . TINYPASS_FAVICON . '">&nbsp;' . __('Tinypass Options'), 'tinypass_meta_box_display', 'page', 'side'
	);
}

function tinypass_meta_box_display($post) {
	$storage = new TPStorage();
	$postSettings = $storage->getPostSettings($post->ID);
	tinypass_post_header_form($postSettings);
}

/* Adding scripts to admin pages */
add_action('admin_enqueue_scripts', 'tinypass_add_admin_scripts');

function tinypass_add_admin_scripts() {
	wp_enqueue_script("jquery");
	wp_enqueue_script("jquery-ui");
	wp_enqueue_script('jquery-ui-dialog');
	wp_enqueue_script('tinypass_admin', TINYPASSS_PLUGIN_PATH . 'js/tinypass_admin.js', array('jquery'), false, false);
  wp_enqueue_style('tinypass.css', TINYPASSS_PLUGIN_PATH . 'css/tinypass.css');
	wp_enqueue_style('jquery-ui-1.8.2.custom.css', TINYPASSS_PLUGIN_PATH . 'css/jquery-ui-1.8.2.custom.css');
}

?>