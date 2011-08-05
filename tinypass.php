<?php
/**
 * @package TinyPass
 * @version 1.0
 */
/*
Plugin Name: TinyPass
Plugin URI: http://www.tinypass.com
Description: TinyPass plugin for wordpress
Author: TinyPass
Version: 1.0
Author URI: http://www.tinypass.com
*/

define("TINYPASS_INLINE", '/(.?)<(tinypass)\b(.*?)(?:(\/))?>(?:(.+?)<\/\2>)?(.?)/s');
define("TINYPASS_INLINE_REPLACE", '/(.?)<(tinypass)\b(.*?)(?:(\/))?>(?:(.+?)<\/\2>)?(.?).*/s');

$tinypass_instance = null;


function tinypass_register_custom_database_tables() {
	global $wpdb;
	$wpdb->tinypass_ref = $wpdb->prefix . 'tinypass_ref';
}
add_action( 'init', 'tinypass_register_custom_database_tables' );

//setup
if ( is_admin() ) {
	require_once dirname( __FILE__ ) . '/tinypass-install.php';
	require_once dirname( __FILE__ ) . '/tinypass-admin.php';
	require_once dirname( __FILE__ ) . '/tinypass-form.php';
}

register_activation_hook(__FILE__,'tinypass_activate');
register_deactivation_hook(__FILE__,'tinypass_deactivate');
register_uninstall_hook(__FILE__, 'tinypass_uninstall');

add_action('save_post', 'tinypass_save_postdata');
add_filter('the_content', 'tinypass_check_content', 10);

function tinypass_save_postdata($post_id) {

	// verify this came from the our screen and with proper authorization,
	// because save_post can be triggered at other times

	/*
	if(isset($_POST['tinypass_noncename'])) {
		if ( !wp_verify_nonce($_POST['tinypass_noncename'], 'tinypass_post_save') )
			return $post_id;
	}

	// verify if this is an auto save routine.
	// If it is our form has not been submitted, so we dont want to do anything
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
		return $post_id;
	*/


	/*
	// Check permissions
	if ( 'page' == $_POST['post_type'] ) {
		if ( !current_user_can( 'edit_page', $post_id ) )
			return $post_id;
	}
	else {
		if ( !current_user_can( 'edit_post', $post_id ) )
			return $post_id;
	}
	*/

	delete_post_meta($post_id, 'tinypass');

	$data = array();

	if(isset($_POST['tinypass']))
		$data = $_POST['tinypass'];

	update_post_meta($post_id, 'tinypass', $data, true);

	return $data;
}

/**
 * Wrap the TinyPass options in a more useful class
 */
function meta_to_object($meta) {
	$options = new TinyPassOptions($meta);
	return $options;
}

/**
 * Load and init global tinypass settings
 */
function tinypass_load_settings() {
	$settings = get_option('tinypass_settings', array());

	//default settings
	if(count($settings) == 0)
		return array('env'=>0, 'aid'=>'', 'secret_key'=>'XX', 'enabled'=>0);

	$tinypass_enabled = isset($settings['enabled']) ? $settings['enabled'] : 0;

	if($tinypass_enabled == 'off' || (is_numeric($tinypass_enabled) && $tinypass_enabled == 0))
		$settings['enabled'] = 0;
	else
		$settings['enabled'] = 1;


	if($settings['env'] == 0) {
		$settings['url'] = 'http://sandbox.tinypass.com';
		$settings['aid'] = $settings['aid_sand'];
		$settings['secret_key'] = $settings['secret_key_sand'];
	}else {
		$settings['url'] = 'https://api.tinypass.com';
		$settings['aid'] = $settings['aid_prod'];
		$settings['secret_key'] = $settings['secret_key_prod'];
	}

	return $settings;

}

/**
 * Main function for displaying TinyPass button on restricted post/pages
 */
function tinypass_check_content($content) {

	global $post;
	global $tinypass_instance;

	$settings = tinypass_load_settings();

	if($settings['enabled'] == false)
		return $content;

	//pull tinypass options from post_meta
	$meta = get_post_meta($post->ID, 'tinypass', true);
	$postOptions = meta_to_object($meta);

	//pull tinypass options from tag
	$terms = get_the_terms($post->ID, 'post_tag');
	$tags = tinypass_enabled_tags();

	$tagOptions = meta_to_object(array());

	if($terms) {
		foreach($terms as $term) {
			if(array_key_exists($term->term_id, $tags)) {
				$tagOptions = meta_to_object($tags[$term->term_id]['data']);
			}
		}
	}


	$m = array();
	if(preg_match(TINYPASS_INLINE, $content, $m)) {

		$tag = $m[2];
		$attr = shortcode_parse_atts( $m[3] );

		$inline = array();

		$inline['en'] = isset($settings['inline']) && $settings['inline'];
		$inline['resource_id'] = 'wp_post_' . $post->ID;
		$inline['po_en1'] = 1;

		if(isset($attr['price']))
			$inline['po_p1'] = $attr['price'];

		if(isset($attr['access'])) {
			$sp = preg_split('/\s+/', $attr['access']);
			if(count($sp) == 2) {
				$inline['po_ap1'] = $sp[0];
				$inline['po_type1'] = $sp[1];
			}
		}

		if(isset($attr['name']) && $attr['name'])
			$inline['resource_name'] = $attr['name'];
		else
			$inline['resource_name'] = $post->post_title;

		if(isset($attr['start']))
			$inline['po_st1'] = $attr['start'];

		if(isset($attr['end']))
			$inline['po_et1'] = $attr['end'];


		if(isset($attr['caption']))
			$inline['po_cap1'] = $attr['caption'];

		if(isset($attr['tag']) && $attr['tag'] != '') {
			$term = get_term_by('name', $attr['tag'], 'post_tag');
			if(array_key_exists($term->term_id, $tags)) {
				$tagOptions = meta_to_object($tags[$term->term_id]['data']);
			}
		}
		else {
			$postOptions = meta_to_object($inline);
		}

	}


	if($postOptions->isEnabled() || $tagOptions->isEnabled()) {
		include_once dirname( __FILE__ ) . '/api/TinyPass.php';

		if($postOptions->getResourceId() == '') {
			$resource_id = preg_replace('/\s+/', '_', $resource_id);
			$postOptions->setResourceId("wp_post_" . strval($post->ID) . "");
		}

		if($postOptions->getResourceName() == '') {
			$postOptions->setResourceName($post->post_title);
		}

		$message = $settings['access_message'];

		//init TP
		if($tinypass_instance == null)
			$tinypass_instance = new TinyPass($settings['url'], $settings['aid'], $settings['secret_key']);

		$tp = $tinypass_instance;

		$ticket = null;
		$upsell = null;

		if($postOptions->isEnabled()) {
			$ticket = tinypass_create_ticket($tp, $postOptions);
		}

		if($tagOptions->isEnabled()) {
			$upsell = tinypass_create_ticket($tp, $tagOptions);
			if($ticket && $upsell) {
				$ticket->setUpSellTicket($upsell);
			} else if ($ticket == null && $upsell) {
				$ticket = $upsell;
			}
		}

		$resource_id = $ticket->getResource()->getRID();

		//check single ticket
		if($tp->isAccessGranted($ticket)) {
			return $content;
		}


		//check upsell
		if($upsell != null && $tp->isAccessGranted($upsell)) {
			return $content;
		}

		$tp->getWebWidget()->addTicket($ticket);

		if(is_page() == false && is_single() == false) {
			if(has_excerpt()) {
				$content = get_the_excerpt();
			}else {
				$content = tinypass_trim_excerpt($content);
				$excerpt_more = apply_filters('excerpt_more', ' ' . '[...]');
				$content .= $excerpt_more;
			}
			return $content;
		}

		if(has_excerpt()) {
			$content = get_the_excerpt();
		}else {
			$content = tinypass_trim_excerpt($content);
		}

		$tp->getWebWidget()->setCallBackFunction('tinypass_reloader');
		$code = $tp->getWebWidget()->getCode();


		$content .= '
			<script>
				function tinypass_reloader(status){
					if(status.state == "granted"){
						window.location.reload();
					}
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
			<div class="tinypass_access_message">'. stripslashes($settings['access_message']) .'</div>
				<span id="'.$resource_id.'"></span>
			</div>' . $code;

		return $content;
	}else
		return $content;

}

function tinypass_create_ticket($tp, TinyPassOptions $options) {
	if($options == null)
		return null;
	$resource = $tp->initResource($options->getResourceId(), $options->getResourceName());
	$ticket = new TPTicket($resource);

	$pos = array();

	if($options->isEnabled()) {
		for($i = 1; $i <= $options->getNumPrices(); $i++) {

			$po = new TPPriceOption($options->getPrice($i));

			if($options->getAccess($i) != '')
				$po->setAccessPeriod($options->getAccess($i));

			if($options->getCaption($i) != '')
				$po->setCaption($options->getCaption($i));

			if($options->getStartDateSec($i) != '')
				$po->setStartDate($options->getStartDateSec($i));

			if($options->getEndDateSec($i) != '')
				$po->setEndDate($options->getEndDateSec($i));

			$pos[] = $po;

		}
	}

	foreach($pos as $po) {
		$ticket->addPriceOption($po);
	}

	return $ticket;

}
class TinyPassOptions {

	public function  __construct($data) {
		$this->data = $data;

		if($this->_isset('resource_name'))
			$this->resource_name = $data['resource_name'];

		if($this->_isset('resource_id'))
			$this->resource_id = $data['resource_id'];

		$count = 0;
		for($i = 1; $i <= 3; $i++) {
			if($this->_isset('po_en'.$i))
				$count++;
		}

		$this->num_prices = $count;

	}

	public function isEnabled() {
		return $this->_isset('en');
	}

	private function _isset($field) {
		return isset($this->data[$field]) && ($this->data[$field] || $this->data[$field] == 'on');
	}

	public function getResourceName() {
		return $this->resource_name;
	}

	public function setResourceName($s) {
		$this->resource_name = $s;
	}

	public function getResourceId() {
		return $this->resource_id;
	}

	public function setResourceId($s) {
		$this->resource_id = $s;
	}

	public function getNumPrices() {
		return $this->num_prices;
	}

	public function getPrice($i) {
		return $this->data["po_p$i"];
	}

	public function getAccess($i) {
		if($this->data["po_ap$i"] == '' ||  $this->data["po_type$i"] == '')
			return '';
		return $this->data["po_ap$i"] . " " . $this->data["po_type$i"];
	}

	public function getCaption($i) {
		return $this->data["po_cap$i"];
	}

	public function getStartDateSec($i) {
		return strtotime($this->data["po_st$i"]);
	}

	public function getEndDateSec($i) {
		return strtotime($this->data["po_et$i"]);
	}


}

function tinypass_trim_excerpt($text) {

	$text = strip_shortcodes( $text );

	$text = str_replace(']]>', ']]&gt;', $text);
	$text = preg_replace('/<!--more-->.*/s', '', $text);
	$text = strip_tags($text);
	$excerpt_length = apply_filters('excerpt_length', 55);

	$text = preg_replace(TINYPASS_INLINE_REPLACE, '', $text);

	//$excerpt_more = apply_filters('excerpt_more', ' ' . '[...]');
	$words = preg_split("/[\n\r\t ]+/", $text, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY);
	if ( count($words) > $excerpt_length ) {
		array_pop($words);
		$text = implode(' ', $words);
		$text = $text . $excerpt_more;
	} else {
		$text = implode(' ', $words);
	}
	//return apply_filters('wp_trim_excerpt', $text, $raw_excerpt);
	return $text;

}

function tinypass_enabled_tags() {
	global $wpdb;

	$results = wp_cache_get("tinypass_enabled_tags");


	$terms = array();
	if($results == false) {
		$results = $wpdb->get_results("select * from $wpdb->tinypass_ref ", ARRAY_A);

		foreach($results as $i => $row) {
			$row['data'] = unserialize($row['data']);
			$terms[$row['term_id']] = $row;
		}

		wp_cache_set("tinypass_enabled_tags", $results);
	}

	return $terms;

}


?>
