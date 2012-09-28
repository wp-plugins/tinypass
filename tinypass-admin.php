<?php

define('TINYPASS_FAVICON', 'http://www.tinypass.com/favicon.ico');

//add_action('delete_term', 'tinypass_term_deleted');
add_action("admin_menu", 'tinypass_add_admin_pages');

function tinypass_add_admin_pages() {
	add_menu_page('TinyPass', 'TinyPass', 'manage_options', 'TinyPass', 'tinypass_mode_settings', TINYPASS_FAVICON);
	//add_submenu_page('TinyPass', 'TinyPass Tag Options', 'Tag Options', 'edit_plugins', 'TinyPassTagOptions', 'tinypass_admin_tags');
	add_submenu_page('TinyPass', 'Paywall Settings', 'Paywall Settings', 'edit_plugins', 'TinyPass', 'tinypass_mode_settings');
	add_submenu_page('TinyPass', 'Pay per Post', 'Pay per Post', 'edit_plugins', 'TinyPassPPVSettings', 'tinypass_ppv_settings');
	add_submenu_page('TinyPass', 'Settings', 'Settings', 'edit_plugins', 'TinyPassSiteSettings', 'tinypass_site_settings');
//	add_submenu_page('TinyPass', 'Paywalls', 'Paywalls', 'edit_plugins', 'TinyPassPaywalls', 'tinypass_list_paywalls');

	tinypass_include();

	wp_enqueue_script('suggest');

	include_once (dirname(__FILE__) . '/tinypass-mode-settings.php');
	include_once (dirname(__FILE__) . '/tinypass-ppv-settings.php');
	include_once (dirname(__FILE__) . '/tinypass-site-settings.php');
	include_once (dirname(__FILE__) . '/tinypass-list-paywalls.php');
}

/* Post/Page edit forms meta boxes */
add_action('add_meta_boxes', 'tinypass_add_meta_boxes');

function tinypass_add_meta_boxes() {


	$storage = new TPStorage();
	$ss = $storage->getSiteSettings();

	if (!$ss->isPPVEnabled()) {
		return;
	}

	add_meta_box(
					'tinypass_post_options', '<img src="' . TINYPASS_FAVICON . '">&nbsp;' . __('TinyPass Post Options'), 'tinypass_meta_box_display', 'post', 'side'
	);
	add_meta_box(
					'tinypass_post_options', '<img src="' . TINYPASS_FAVICON . '">&nbsp;' . __('TinyPass Page Options'), 'tinypass_meta_box_display', 'page', 'side'
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
	global $wp_version;


	wp_enqueue_script("jquery-core");
	wp_enqueue_script("jquery-ui");
	wp_enqueue_script('jquery-ui-dialog');

	if (version_compare($wp_version, '3.1', '<')) {
		wp_enqueue_script('jquery-ui-slider', TINYPASSS_PLUGIN_PATH . 'js/ui.slider.min-1.7.3.js', array('jquery-ui-core'), false, false);
		wp_enqueue_script('jquery-ui-datepicker', TINYPASSS_PLUGIN_PATH . 'js/ui.datepicker.min-1.7.3.js', array('jquery-ui-core'), false, false);
	} else {
		wp_enqueue_script('jquery-ui-slider', TINYPASSS_PLUGIN_PATH . 'js/ui.slider.min-1.8.10.js', array('jquery-ui-core'), false, true);
		wp_enqueue_script('jquery-ui-datepicker', TINYPASSS_PLUGIN_PATH . 'js/ui.datepicker.min-1.8.10.js', array('jquery-ui-core'), false, false);
	}
	wp_enqueue_script('jquery-ui-timepicker', TINYPASSS_PLUGIN_PATH . 'js/ui.timepicker.min.js', array('jquery-ui-datepicker'), false, false);
	wp_enqueue_script('tinypass_admin', TINYPASSS_PLUGIN_PATH . 'js/tinypass_admin.js', array('jquery'), false, false);
	wp_enqueue_style('jquery-ui-1.8.2.custom.css', TINYPASSS_PLUGIN_PATH . 'css/jquery-ui-1.8.2.custom.css');
}

function tinypass_fetch_tag_meta($termId = null) {
	global $wpdb;

	$query = " select t.name, r.term_id, type, data from $wpdb->tinypass_ref r, $wpdb->terms t
							where t.term_id = r.term_id";

	if ($termId)
		$query .= " and t.term_id = $termId ";

	$query .= " order by t.name asc ";

	$results = $wpdb->get_results($query);

	$terms = array();
	foreach ($results as $row) {
		$term = array();
		$term['term_id'] = $row->term_id;
		$term['name'] = $row->name;
		$term['type'] = $row->type;
		$term['meta'] = unserialize($row->data);
		$term['meta']['term_id'] = $row->term_id;
		$term['meta']['resource_id'] = $row->name;
		$terms[] = $term;
	}

	if ($termId && count($terms))
		return $terms[0]['meta'];

	return $terms;
}
?>