<?php

add_action('delete_term', 'tinypass_term_deleted');
add_action("admin_menu", 'tinypass_add_admin_pages');

function tinypass_add_admin_pages() {
	add_menu_page('TinyPass', 'TinyPass', 'manage_options', 'TinyPass', 'tinypass_admin_settings', 'http://www.tinypass.com/favicon.ico');
	add_submenu_page('TinyPass', 'TinyPass Settings', 'Settings', 10, 'TinyPass', 'tinypass_admin_settings');
	add_submenu_page('TinyPass', 'TinyPass Tag Options', 'Tag Options', 10, 'TinyPassTagOptions', 'tinypass_admin_tags');
}

/* Post Form */
add_action('add_meta_boxes', 'tinypass_post_options_box');

function tinypass_post_options_box() {
	add_meta_box(
					'tinypass_post_options',
					'<img src="http://www.tinypass.com/favicon.ico">&nbsp;' . __( 'TinyPass Options'),
					'tinypass_post_options_box_display',
					'post',
					'side'
	);
	add_meta_box(
					'tinypass_post_options',
					__( 'TinyPass Options'),
					'tinypass_post_options_box_display',
					'page',
					'side'
	);
}

function tinypass_post_options_box_display($post) {
	$meta = get_post_meta($post->ID, 'tinypass', true);
	tinypass_post_header_form($meta);
}

global $wp_version;

define( 'TINYPASSS_PLUGIN_PATH', WP_PLUGIN_URL . '/' . str_replace( basename( __FILE__ ), "", plugin_basename( __FILE__ ) ) );

if (version_compare($wp_version, '3.1', '<')) {
	wp_enqueue_script( 'jquery-ui-slider', TINYPASSS_PLUGIN_PATH . 'js/ui.slider.min-1.7.3.js', array( 'jquery-ui-core' ), false, true );
	wp_enqueue_script( 'jquery-ui-datepicker', TINYPASSS_PLUGIN_PATH . 'js/ui.datepicker.min-1.7.3.js', array( 'jquery-ui-core' ), false, true );
} else {
	wp_enqueue_script( 'jquery-ui-slider', TINYPASSS_PLUGIN_PATH . 'js/ui.slider.min-1.8.10.js', array( 'jquery-ui-core' ), false, true );
	wp_enqueue_script( 'jquery-ui-datepicker', TINYPASSS_PLUGIN_PATH . 'js/ui.datepicker.min-1.8.10.js', array( 'jquery-ui-core' ), false, true );
}
wp_enqueue_script( 'jquery-ui-timepicker', TINYPASSS_PLUGIN_PATH . 'js/ui.timepicker.min.js', array( 'jquery-ui-datepicker' ), false, true );
wp_enqueue_script( 'tinypass_admin', TINYPASSS_PLUGIN_PATH . 'js/tinypass_admin.js', array('jquery' ), false, true );
wp_enqueue_style( 'jquery-ui-1.8.2.custom.css', TINYPASSS_PLUGIN_PATH . 'css/jquery-ui-1.8.2.custom.css' );


function tinypass_admin_settings() {

	if ( isset($_POST['_Submit']) ) {
		update_option( 'tinypass_settings', $_POST['tinypass']);
	}

	$options = get_option('tinypass_settings');

	?>
<div id="poststuff" class="metabox-holder has-right-sidebar">

		<?php if ( !empty($_POST['_Submit'] ) ) : ?>
	<div id="message" class="updated fade"><p><strong><?php _e('Options saved.') ?></strong></p></div>
		<?php endif; ?>

	<div class="wrap">
		<h2><?php _e('TinyPass Settings'); ?></h2>
		<form action="" method="post" id="tinypass-conf" style="margin-left:30px; ">


			<div class="postbox">
				<h3><?php _e('TinyPass Enabled') ?> </h3>
				<div class="inside">
					<table class="form-table">

						<tr>
							<td>
								TinyPass Enabled
							</td>
							<td>
									<?php if($options['enabled'] == 'on' || $options['enabled']): ?>
								<input type="checkbox" name="tinypass[enabled]" checked=true>
									<?php else: ?>
								<input type="checkbox" name="tinypass[enabled]">
									<?php endif; ?>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php _e('TinyPass Environment'); ?></th>
							<td>
									<?php if($options['env'] == 0): ?>
								<input type="radio" name="tinypass[env]" value="0" checked=true><label>Sandbox</label><br>
								<input type="radio" name="tinypass[env]" value="1"><label>Production</label>
									<?php else: ?>
								<input type="radio" name="tinypass[env]" value="0"><label>Sandbox</label><br>
								<input type="radio" name="tinypass[env]" value="1"checked=true><label>Production</label>
									<?php endif; ?>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<div class="postbox">
				<h3><?php _e('Application IDs and Keys'); ?> </h3>
				<div class="inside">

					<table class="form-table">

						<tr valign="top">
							<th scope="row"><?php _e('Application ID (Sandbox)'); ?></th>
							<td>
								<input id="aid_sand" name="tinypass[aid_sand]" type="text" size="10" maxlength="10" value="<?php echo $options['aid_sand']; ?>"/>
								<span class="description">The application ID that will corresponding to this website.  Retreived from your account on <a href="http://sandbox.tinypass.com/member/merch">sandbox.tinypass.com</a></span>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row"><?php _e('Application Secret Key (Sandbox)'); ?></th>
							<td>
								<input id="secret_key_sand" name="tinypass[secret_key_sand]" type="text" size="40" maxlength="40" value="<?php echo $options['secret_key_sand']; ?>" style="" />
								<span class="description">Retreived from your account on <a href="http://sandbox.tinypass.com/member/merch">sandbox.tinypass.com</a></span>
							</td>
						</tr>
						<tr><td></td></tr>
						<tr><td></td></tr>
						<tr valign="top">
							<th scope="row"><?php _e('Application ID (Live)'); ?></th>
							<td>
								<input id="aid_prod" name="tinypass[aid_prod]" type="text" size="10" maxlength="10" value="<?php echo $options['aid_prod']; ?>"/>
								<span class="description">The application ID that will corresponding to this website.  Retreived from your account on <a href="http://www.tinypass.com/member/merch">www.tinypass.com</a></span>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row"><?php _e('Application Secret Key (Live)'); ?></th>
							<td>
								<input id="secret_key_prod" name="tinypass[secret_key_prod]" type="text" size="40" maxlength="40" value="<?php echo $options['secret_key_prod']; ?>" style="" />
								<span class="description">Retreived from your account on <a href="http://www.tinypass.com/member/merch">www.tinypass.com</a></span>
							</td>
						</tr>

					</table>
				</div>
			</div>

			<div class="postbox">
				<h3><?php _e('Other'); ?> </h3>
				<div class="inside">

					<table class="form-table">
						<tr valign="top">
							<th scope="row"><?php _e('Inline TinyPass tags allowed'); ?></th>
							<td>
									<?php if($options['inline'] == 'on' || $options['inline']): ?>
								<input type="checkbox" name="tinypass[inline]" checked=true>
									<?php else: ?>
								<input type="checkbox" name="tinypass[inline]">
									<?php endif; ?>

								<br>
								<span class="description">Allow for the use of inline tinypass tags with the post or page content.  When disabled
								content will not be blocked even though the tag is present.</span>

								<p class="description">
									Only one inline tag is allowed.  If there is more than one, the first tag will be processed
								</p>
								<p class="description">
									<strong>Example:</strong>
											<br> &lt;tinypass price="1.99" access="3 days"/&gt;
											<br> &lt;tinypass price="1.99" access="3 days" caption="For Download"/&gt;
											<br>
											<br> For tags: (must be defined in TinyPass TagOptions)
											<br> &lt;tinypass tag="tagName"/&gt;
								</p>

							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e('Denied access message'); ?></th>
							<td>
								<textarea cols="80" rows="12" name="tinypass[access_message]" ><?php echo stripslashes($options['access_message'])?></textarea>
								<br>
								<span class="description">This message will be displayed when access is denied to a resource</span>
							</td>
						</tr>

					</table>
				</div>
			</div>

			<p>
				<input type="submit" name="_Submit" id="publish" value="Save Changes" tabindex="4" class="button-primary" />
			</p>

		</form>
	</div>
</div>

	<?php
}

function tinypass_fetch_tag_meta($termId = null) {
	global $wpdb;

	$query = " select t.name, r.term_id, type, data from $wpdb->tinypass_ref r, $wpdb->terms t
							where t.term_id = r.term_id";

	if($termId)
		$query .= " and t.term_id = $termId ";

	$query .= " order by t.name asc ";

	$results = $wpdb->get_results($query);

	$terms = array();
	foreach($results as $row) {
		$term = array();
		$term['term_id'] = $row->term_id;
		$term['name'] = $row->name;
		$term['type'] = $row->type;
		$term['meta'] = unserialize($row->data);
		$term['meta']['term_id'] = $row->term_id;
		$term['meta']['resource_id'] = $row->name;
		$terms[] = $term;
	}

	if($termId && count($terms))
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
			<h3><?php _e('TinyPass Enabled Tags - bundle content together for purchase'); ?> </h3>
			<div class="inside">

				<div>
					<input class="button" type="button" hef="#" onclick="tp_showTinyPassPopup('tag');" value="Add Tag">
				</div>

				<input id="tp_enabled" type="hidden" name="tinypass[en]" checked=true>
				<div id="tp_dialog" title="<img src='http://www.tinypass.com/favicon.ico'> TinyPass Post Options" style="display:none;width:650px;"></div>
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
			<th width="200">Tag</th>
			<th>Details</th>
			<th width="200">Action</th>
		</tr>
			<?php foreach($terms as $term) : ?>
		<tr>
			<td><?php echo $term['name'] ?></td>
			<td><?php echo tinypass_options_overview($term['meta']) ?></td>
			<td>
				<input id="tp_modify_button" class="button" type="button" hef="#" onclick="return tp_showTinyPassPopup('tag', <?php echo $term['term_id'] ?>);" value="Modify">
				<input id="tp_modify_button" class="button" type="button" hef="#" onclick="if(confirm('Are you sure you want to delete these TinyPass settings')) tp_deleteTagOption(<?php echo $term['term_id'] ?>);" value="Delete">
			</td>

		</tr>
			<?php endforeach; ?>
	</table>
</div>

	<?php
}
?>