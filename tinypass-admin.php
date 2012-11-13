<?php
define('TINYPASS_FAVICON', 'http://www.tinypass.com/favicon.ico');

tinypass_include();

add_action("admin_menu", 'tinypass_add_admin_pages');

require_once (dirname(__FILE__) . '/tinypass-mode-settings.php');
require_once (dirname(__FILE__) . '/tinypass-ppp-settings.php');
require_once (dirname(__FILE__) . '/tinypass-site-settings.php');
require_once (dirname(__FILE__) . '/tinypass-paywalls.php');

function tinypass_add_admin_pages() {
	add_menu_page('Tinypass', 'Tinypass', 'edit_plugins', 'tinypass.php', 'tinypass_paywalls_list', TINYPASS_FAVICON);
	add_submenu_page('', 'Paywalls', 'Paywalls', 'edit_plugins', 'TinyPassPaywalls', 'tinypass_paywalls_list');
	add_submenu_page('tinypass.php', 'Settings', 'Settings', 'edit_plugins', 'TinyPassSiteSettings', 'tinypass_site_settings');
	//add_submenu_page('tinypass.php', 'Guide', 'Guide', 'edit_plugins', 'TinyPassGuide', 'http://www.google.com');
	add_submenu_page('', 'Edit Paywall', '', 'edit_plugins', 'TinyPassEditPaywall', 'tinypass_mode_settings');

	wp_enqueue_script('suggest');

}

/* Post/Page edit forms meta boxes */
add_action('add_meta_boxes', 'tinypass_add_meta_boxes');

function tinypass_add_meta_boxes() {

  $ss = tinypass_load_settings();

	if (!$ss->isPPPEnabled()) {
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