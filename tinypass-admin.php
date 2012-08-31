<?php
global $wp_version;

add_action('delete_term', 'tinypass_term_deleted');
add_action("admin_menu", 'tinypass_add_admin_pages');

function tinypass_add_admin_pages() {
	add_menu_page('TinyPass', 'TinyPass', 'manage_options', 'TinyPass', 'tinypass_site_settings', 'http://www.tinypass.com/favicon.ico');
	add_submenu_page('TinyPass', 'TinyPass Settings', 'Settings', 10, 'TinyPass', 'tinypass_site_settings');
	add_submenu_page('TinyPass', 'TinyPass Tag Options', 'Tag Options', 10, 'TinyPassTagOptions', 'tinypass_admin_tags');
	add_submenu_page('TinyPass', 'TinyPass Mode', 'TinyPass Mode', 10, 'TinyPassModeSettings', 'tinypass_mode_settings');

	include_once (dirname(__FILE__) . '/tinypass-mode-settings.php');
	include_once (dirname(__FILE__) . '/tinypass-site-settings.php');
}

/* Post/Page edit forms meta boxes */
add_action('add_meta_boxes', 'tinypass_add_meta_boxes');

function tinypass_add_meta_boxes() {
	add_meta_box(
					'tinypass_post_options', '<img src="http://www.tinypass.com/favicon.ico">&nbsp;' . __('TinyPass Post Options'), 'tinypass_meta_box_display', 'post', 'side'
	);
	add_meta_box(
					'tinypass_post_options', '<img src="http://www.tinypass.com/favicon.ico">&nbsp;' . __('TinyPass Page Options'), 'tinypass_meta_box_display', 'page', 'side'
	);
}

function tinypass_meta_box_display($post) {
	$meta = get_post_meta($post->ID, 'tinypass', true);
	tinypass_post_header_form($meta);
}

/* Adding scripts to admin pages */
add_action('admin_enqueue_scripts' . $page, 'tinypass_add_admin_scripts');

function tinypass_add_admin_scripts() {

	define('TINYPASSS_PLUGIN_PATH', WP_PLUGIN_URL . '/' . str_replace(basename(__FILE__), "", plugin_basename(__FILE__)));

	wp_enqueue_script("jquery-core");
	wp_enqueue_script("jquery-ui");
	wp_enqueue_script('jquery-ui-dialog');

	if (version_compare($wp_version, '3.1', '<')) {
		wp_enqueue_script('jquery-ui-slider', TINYPASSS_PLUGIN_PATH . 'js/ui.slider.min-1.7.3.js', array('jquery-ui-core'), false, true);
		wp_enqueue_script('jquery-ui-datepicker', TINYPASSS_PLUGIN_PATH . 'js/ui.datepicker.min-1.7.3.js', array('jquery-ui-core'), false, true);
	}
	else {
		wp_enqueue_script('jquery-ui-slider', TINYPASSS_PLUGIN_PATH . 'js/ui.slider.min-1.8.10.js', array('jquery-ui-core'), false, true);
		wp_enqueue_script('jquery-ui-datepicker', TINYPASSS_PLUGIN_PATH . 'js/ui.datepicker.min-1.8.10.js', array('jquery-ui-core'), false, true);
	}
	wp_enqueue_script('jquery-ui-timepicker', TINYPASSS_PLUGIN_PATH . 'js/ui.timepicker.min.js', array('jquery-ui-datepicker'), false, true);
	wp_enqueue_script('tinypass_admin', TINYPASSS_PLUGIN_PATH . 'js/tinypass_admin.js', array('jquery'), false, true);
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

function tinypass_admin_tags() {
	?>
	<div id="poststuff" class="metabox-holder has-right-sidebar">

		<div class="wrap">
			<h2><?php _e('Tag Options'); ?></h2>

			<?php if (function_exists('wp_nonce_field')) $wpnonce = wp_nonce_field('tinypass-tagoptions'); ?>

			<div class="postbox">

				<h3><?php _e('TinyPass enabled tags - bundle content together for purchase'); ?> </h3>
				<div class="inside">

					<div>
						<input class="button" type="button" hef="#" onclick="tinypass.showTinyPassPopup('tag');" value="<?php _e('Configure new tag') ?>">
					</div>

					<input id="tp_enabled" type="hidden" name="tinypass[en]" checked=true>
					<div id="tp_dialog" title="<img src='http://www.tinypass.com/favicon.ico'> TinyPass Tag Settings" style="display:none;width:650px;"></div>
					<div id="tp_hidden_options">
						<?php echo tinypass_admin_tags_body() ?>
					</div>

				</div>
			</div>
		</div>
	</div>


	<?php
}

function tinypass_term_deleted($term = null, $tt_id = null, $taxonomy = null) {
	global $wpdb;
	$wpdb->query("delete from $wpdb->tinypass_ref where term_id = $term ");
}

function tinypass_admin_tags_body() {

	$terms = tinypass_fetch_tag_meta();
	?>

	<?php if (function_exists('wp_nonce_field')) $wpnonce = wp_nonce_field('tinypass-tagoptions'); ?>

	<div class="inside">

		<table class="wp-list-table widefat fixed media" style="width:800px">
			<tr>
				<th width="200"><?php _e('Tag') ?></th>
				<th><?php _e('Details') ?></th>
				<th width="200"><?php _e('Action') ?></th>
			</tr>
			<?php wp_nonce_field('tinypass_options', 'tinypass_nonce'); ?>
			<?php foreach ($terms as $term) : ?>
				<tr>
					<td><?php echo $term['name'] ?></td>
					<td><?php echo tinypass_options_overview($term['meta']) ?></td>
					<td>
						<input id="tp_modify_button" class="button" type="button" hef="#" onclick="return tinypass.showTinyPassPopup('tag', <?php echo $term['term_id'] ?>);" value="<?php _e('Modify') ?>">
						<input id="tp_modify_button" class="button" type="button" hef="#" onclick="if(confirm('Are you sure you want to delete these TinyPass settings')) tinypass.deleteTagOption(<?php echo $term['term_id'] ?>);" value="<?php _e('Delete') ?>">
					</td>

				</tr>
			<?php endforeach; ?>
		</table>
	</div>

	<?php
}
?>
