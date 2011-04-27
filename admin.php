<?php
add_action( 'admin_menu', 'tinypass_config_page' );
add_filter('plugin_action_links', 'tinypass_plugin_links', 10, 2 );


function tinypass_plugin_links($links, $file) {
	if (preg_match('/^tinypass\//',$file)) {
		$settings_link = '<a href="plugins.php?page=tinypass-config">Settings</a>';
		array_unshift($links, $settings_link);
	}
	return $links;
}

function tinypass_config_page() {
	if ( function_exists('add_submenu_page') )
		add_submenu_page('plugins.php', __('TinyPass Configuration'), __('TinyPass Configuration'), 'manage_options', 'tinypass-config', 'tinypass_conf');
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


function tinypass_conf() {
	global $akismet_nonce, $wpcom_api_key;

	if ( isset($_POST['submit']) ) {

		if ( isset( $_POST['aid'] ) )
			update_option( 'tinypass_aid', $_POST['aid'] );

		if ( isset( $_POST['secret_key'] ) )
			update_option( 'tinypass_secret_key', $_POST['secret_key']);

		if ( isset( $_POST['access_message'] ) )
			update_option( 'tinypass_access_message', $_POST['access_message']);

		if ( isset( $_POST['env'] ) )
			update_option( 'tinypass_env', $_POST['env']);
	}

	?>

	<?php if ( !empty($_POST['submit'] ) ) : ?>
<div id="message" class="updated fade"><p><strong><?php _e('Options saved.') ?></strong></p></div>
	<?php endif; ?>
<div class="wrap">
	<h2><?php _e('TinyPass Configuration'); ?></h2>
	<form action="" method="post" id="tinypass-conf" style="margin-left:30px; ">

		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e('TinyPass Application ID'); ?></th>
				<td>
					<input id="aid" name="aid" type="text" size="10" maxlength="10" value="<?php echo get_option('tinypass_aid'); ?>"/>
					<span class="description">The application ID that will corresponding to this website.  Retreived from your account on <a href="http://www.tinypass.com/member/merch">www.tinypass.com</a></span>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php _e('TinyPass Application Secret Key'); ?></th>
				<td>
					<input id="secret_key" name="secret_key" type="text" size="40" maxlength="40" value="<?php echo get_option('tinypass_secret_key'); ?>" style="" />
					<span class="description">Retreived from your account on <a href="http://www.tinypass.com/member/merch">www.tinypass.com</a></span>
				</td>
			</tr>


			<tr valign="top">
				<th scope="row"><?php _e('Denied access message'); ?></th>
				<td>
					<input id="access_message" name="access_message" type="text" size="60" maxlength="255" value="<?php echo get_option('tinypass_access_message'); ?>" style="" />
					<br>
					<span class="description">This message will be displayed when access is denied to a resource</span>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php _e('TinyPass Environment'); ?></th>
				<td>
						<?php if(get_option('tinypass_env', 0) == 0): ?>
					<input type="radio" name="env" value="0" checked=true><label>Sandbox</label><br>
					<input type="radio" name="env" value="1"><label>Production</label>
						<?php else: ?>
					<input type="radio" name="env" value="0"><label>Sandbox</label><br>
					<input type="radio" name="env" value="1"checked=true><label>Production</label>
						<?php endif; ?>
				</td>
			</tr>


		</table>

		<p class="submit"><input type="submit" name="submit" value="<?php _e('Update'); ?>" /></p>
	</form>
</div>
	<?php
}