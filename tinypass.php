<?php
/**
 * @package TinyPass
 * @version 0.1
 */
/*
Plugin Name: TinyPass
Plugin URI: http://www.tinypass.com
Description: TinyPass plugin for wordpress
Author: TinyPass
Version: 0.1
Author URI: http://www.tinypass.com
*/

register_activation_hook(__FILE__,'tinypass_install');
register_deactivation_hook(__FILE__,'tinypass_uninstall');

add_action('add_meta_boxes', 'tinypass_post_options_box');
add_action('save_post', 'tinypass_save_postdata');
add_filter('the_content', "tinypass_check_content", 0);

add_filter( 'the_content_more_link', 'my_more_link', 10, 2 );

function tinypass_custom_excerpt_more( $more ) {
	return '';
}

function my_more_link( $more_link, $more_link_text ) {
	return str_replace( $more_link_text, 'XXX Continue reading &rarr;', $more_link );
}

if ( is_admin() )
	require_once dirname( __FILE__ ) . '/admin.php';

function tinypass_save_postdata($post_id) {
	// verify this came from the our screen and with proper authorization,
	// because save_post can be triggered at other times

	if ( !wp_verify_nonce( $_POST['tinypass_noncename'], plugin_basename(__FILE__) ) )
		return $post_id;

	// verify if this is an auto save routine.
	// If it is our form has not been submitted, so we dont want to do anything
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
		return $post_id;


	// Check permissions
	if ( 'page' == $_POST['post_type'] ) {
		if ( !current_user_can( 'edit_page', $post_id ) )
			return $post_id;
	}
	else {
		if ( !current_user_can( 'edit_post', $post_id ) )
			return $post_id;
	}

	// OK, we're authenticated: we need to find and save the data


	delete_post_meta($post_id, 'tinypass_meta');

	$data = $_POST['tinypass'];

	if(isset($data['resource_id']) && $data['resource_id'] == '') {
		$data['resource_id'] = '';
	}

	if(isset($data['resource_name']) && $data['resource_name'] == '') {
		$data['resource_name'] = '';
	}

	update_post_meta($post_id, 'tinypass_meta', $data, true);

	// Do something with $mydata
	// probably using add_post_meta(), update_post_meta(), or
	// a custom table (see Further Reading section below)
	return $data;
}

function tinypass_post_options_box() {
	add_meta_box(
					'tinypass_post_options',
					__( 'TinyPass Options'),
					'tinypass_post_options_box_display',
					'post'
	);
}

function tinypass_post_options_box_display($post) {
	$meta = get_post_meta($post->ID, 'tinypass_meta', true);

	wp_nonce_field( plugin_basename(__FILE__), 'tinypass_noncename' );

	$resource_id = '';
	$resource_name = '';
	$checked = '';
	$hide = 'display:none';

	if(isset($meta['resource_id']))
		$resource_id = $meta['resource_id'];

	if(isset($meta['resource_name']))
		$resource_name = $meta['resource_name'];

	if(isset($meta['enabled']) && $meta['enabled'] == 'on') {
		$checked = 'checked=true';
		$hide = '';
	}

	?>

<table class="form-table">
	<tr>
		<td>
				Enable TinyPass
			<input type="checkbox" name="tinypass[enabled]" <?php echo $checked ?> onchange="tinypass_hidePostOptions(this)">
		</td>
	</tr>
</table>
<table class="form-table" id="tinypass_post_options_form" style="<?php echo $hide ?>" >
	<tr>
		<th>Resource ID</th>
		<td>
			<input type="text" size="50" maxlength="255" name="tinypass[resource_id]" value="<?php echo $resource_id ?>">
			<div class="description">Optional - Leave empty to default to post title</div>
		</td>
	</tr>
	<tr>
		<th>Resource Name</th>
		<td>
			<input type="text" size="50" maxlength="255" name="tinypass[resource_name]" value="<?php echo $resource_name ?>">
			<div class="description">Optional - Leave empty to default to post title</div>
		</td>
	</tr>
	<tr>
		<th>Price Options</th>
		<td>
				<?php echo __tinypass_price_option_display('opt1', $meta)  ?>
			<hr>
				<?php echo __tinypass_price_option_display('opt2', $meta)  ?>
			<hr>
				<?php echo __tinypass_price_option_display('opt3', $meta)  ?>
		</td>
	</tr>



</table>

	<?php
}

function __tinypass_price_option_display($opt, $values) {

	$times = array('hour'=>'hour(s)', 'day'=>'day(s)', 'week'=>'week(s)', 'month'=>'month(s)');

	$price = '';
	$access_period = '';
	$access_period_type = 'day';
	$caption = '';
	$enabled = 0;
	$readonly = '';
	$checked = '';
	$start_time = '';
	$end_time = '';

	if(isset($values[$opt.'_price'])) {
		$price = $values[$opt.'_price'];
	}

	if(isset($values[$opt.'_access_period'])) {
		$access_period = $values[$opt.'_access_period'];
	}

	if(isset($values[$opt.'_access_period_type'])) {
		$access_period_type = $values[$opt.'_access_period_type'];
	}

	if(isset($values[$opt.'_caption'])) {
		$caption = $values[$opt.'_caption'];
	}

	if(isset($values[$opt.'_start_time'])) {
		$start_time = $values[$opt.'_start_time'];
	}

	if(isset($values[$opt.'_end_time'])) {
		$end_time = $values[$opt.'_end_time'];
	}

	if(isset($values[$opt.'_enabled'])) {
		$enabled = 1;
		$checked = 'checked=true';
	}

	if($opt == 'opt1') {
		$enabled = 1;
		$readonly = 'readonly';
		$checked = 'checked=true';
	}

	?>
<style>
	.tinypass_price_options_form td{
		padding:3px;
	}
</style>
<table class="tinypass_price_options_form">
	<tr>
		<td></td>
		<td>Price:</td>
		<td>Access Period:</td>
		<td>Custom Caption:</td>
	</tr>
	<tr>
		<td>
			Enabled
			<input type="checkbox" name="tinypass[<?php echo $opt.'_enabled'?>]" value="<?php echo $enabled ?>" <?php echo $readonly ?> <?php echo $checked ?>   >
		</td>
		<td>
			<input type="text" size="5" maxlength="5" name="tinypass[<?php echo $opt."_price"?>]" value="<?php echo $price ?>">
		</td>
		<td>
			<input type="text" size="5" maxlength="5" name="tinypass[<?php echo $opt."_access_period"?>]" value="<?php echo $access_period ?>">
			<select name="tinypass[<?php echo $opt."_access_period_type" ?>]">
					<?php foreach($times as $key => $value): ?>
						<?php if($key == $access_period_type) { ?>
				<option value="<?php echo $key ?>" selected=true><?php echo $value ?>
								<?php } else { ?>
				<option value="<?php echo $key ?>"><?php echo $value ?>
								<?php } ?>
						<?php endforeach ?>
			</select>
		</td>
		<td>
			<input type="text" size="20" maxlength="20" name="tinypass[<?php echo $opt . "_caption"?>]" value="<?php echo $caption ?>">
		</td>
	</tr>
	<tr>
		<td></td>
		<td colspan="2">
			Start Date: <br><input type="text" class="tinypass-datetimepicker" name="tinypass[<?php echo $opt."_start_time"?>]" value="<?php echo $start_time?>">
		</td>
		<td>
			End Date:&nbsp;&nbsp;<br> <input type="text" class="tinypass-datetimepicker" name="tinypass[<?php echo $opt."_end_time"?>]" value="<?php echo $end_time ?>" >
		</td>
	</tr>
</table>
	<?php
}

function __isset($data, $key) {
	if(isset($data[$key]) && $data[$key] != '') {
		return true;
	}
	return false;
}

function tinypass_check_content($content) {
	global $post;

	$meta = get_post_meta($post->ID, 'tinypass_meta', true);

	if(isset($meta['enabled']) && $meta['enabled'] == 'on') {


		$resource_id = $meta['resource_id'];
		$resource_name = $meta['resource_name'];

		if($resource_id == '') {
			$resource_id = $post->post_name;
		}

		if($resource_name == '') {
			$resource_name = $post->post_title;
		}

		include_once dirname( __FILE__ ) . '/api/TinyPass.php';


		$env = get_option( 'tinypass_env', 0);
		$aid = get_option( 'tinypass_aid', '');
		$secret_key = get_option( 'tinypass_secret_key', '');

		if($env == 0)
			$env = 'http://sandbox.tinypass.com';
		else
			$env = 'https://api.tinypass.com';

		$tp = new TinyPass($env, $aid, $secret_key);
		$resource = $tp->initResource($resource_id, $resource_name);
		$ticket = new TPTicket($resource);

		for($i = 1; $i <= 3; $i++) {
			$key = "opt" . $i;
			if(isset($meta[$key."_enabled"]) && $meta[$key."_enabled"]) {
				$po = new TPPriceOption(($meta[$key."_price"]));

				if(__isset($meta, $key."_caption")) {

					$po->setCaption($meta[$key."_caption"]);

				}else if(__isset($meta, $key."_access_period") && __isset($meta, $key."_access_period_type") ) {

					$po->setAccessPeriod($meta[$key."_access_period"] . ' ' . $meta[$key."_access_period_type"]);
				}

				$ticket->addPriceOption($po);
				$tp->getWebWidget()->addTicket($ticket);
			}
		}

		//check access
		if($tp->isAccessGranted($resource_id)){
			return $content;
		}

		if(is_single() == false) {
			remove_filter('the_content', "tinypass_check_content", 0);
			$content = get_the_excerpt();
			add_filter('the_content', "tinypass_check_content", 0);
			return $content;
		}

		remove_filter('the_content', "tinypass_check_content", 0);
		add_filter('excerpt_more', 'tinypass_custom_excerpt_more', 10, 1 );
		$content = get_the_excerpt();
		add_filter('the_content', "tinypass_check_content", 0);




		$tp->getWebWidget()->setCallBackFunction("tinypass_reloader");
		$code = $tp->getWebWidget()->getCode();

		$content .= '
			<script>
				function tinypass_reloader(){
					if(status.state == "granted")
						window.location.reload();
				}
			</script>
			<style type="text/css">
				.tinypass_button_holder {
					margin-top:20px;
					text-align: center;
				}
				.tinypass_button_holder .tinypass_access_message {
						font-size: 1.1em;
						margin-bottom: 10px;
					}
			</style>
			<div class="tinypass_button_holder">
			<div class="tinypass_access_message">'. get_option("tinypass_access_message").'</div>
				<span id="adsf" class="tinypass_button"></span>
				<span id="'.$resource_id.'"></span>
			</div>
			</div>' . $code;
		return $content;
	}else
		return $content;

}


function tinypass_install() {
	//add_option($name, $value, $deprecated, $autoload);

	add_option('tinypass_aid', 'QdXYalSxyk', '', true);
	add_option('tinypass_private_key', 'zXKpS9HhU9GdOn2jEH0kmzKW6jN4phrSbQ56ip9r', '', true);
	add_option('tinypass_env', 0, '', true);
	add_option('tinypass_access_message', __("To continue, please purchase using TinyPass"), '', true);

}

function tinypass_uninstall() {
	global $wpdb;
	$wpdb->query("delete from $wpdb->postmeta where meta_key = 'tinypass_meta'");
	delete_option('tinypass_aid');
	delete_option('tinypass_private_key');
	delete_option('tinypass_env');
	delete_option('tinypass_access_message');
}


