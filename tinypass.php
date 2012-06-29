<?php
/**
 * @package TinyPass
 * @version 1.4.15
 */
/*
Plugin Name: TinyPass
Plugin URI: http://www.tinypass.com
Description: TinyPass is the best way to charge for access to content on your WordPress site.  To get started: 1) Click the "Activate" link to the left of this description, 2) Go to http://developer.tinypass.com/main/wordpress and follow the installation instructions to create a free TinyPass publisher account and configure the TinyPass plugin for your WordPress site
Author: TinyPass
Version: 1.4.15
Author URI: http://www.tinypass.com
*/

$tinypass_instance = null;
$tinypass_ticket = null;


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
	include_once (dirname (__FILE__) . '/tinymce/plugin.php');

	register_activation_hook(__FILE__,'tinypass_activate');
	register_deactivation_hook(__FILE__,'tinypass_deactivate');
	register_uninstall_hook(__FILE__, 'tinypass_uninstall');
}


add_filter('the_content', 'tinypass_intercept_content', 5);
add_filter('the_content', 'tinypass_append_ticket', 200);

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
function tinypass_intercept_content($content) {

	global $tinypass_ticket;
	global $post;
	global $tinypass_instance;

	$tinypass_ticket = null;
	$settings = tinypass_load_settings();


	//don't process if we aren't on a full single page/post
	if(is_singular() == false)
		return $content;

	if($settings['enabled'] == false)
		return $content;

	//pull tinypass options from post_meta
	$meta = get_post_meta($post->ID, 'tinypass', true);
	$postOptions = meta_to_tp_options($meta);

	//pull tinypass options from tag
	$terms = get_the_terms($post->ID, 'post_tag');
	$tags = tinypass_enabled_tags();

	$tagOptions = meta_to_tp_options(array());

	if($terms) {
		foreach($terms as $term) {
			if(array_key_exists($term->term_id, $tags)) {
				$tagOptions = meta_to_tp_options($tags[$term->term_id]['data']);
			}
		}
	}

	$m = array();

	if($postOptions->isEnabled() || $tagOptions->isEnabled()) {

		define('DONOTCACHEPAGE', true);
		define('DONOTCACHEDB', true);
		define('DONOTMINIFY', true);
		define('DONOTCDN', true);
		define('DONOTCACHCEOBJECT', true);

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

		$offer1 = null;
		$offer2 = null;

		if($postOptions->isEnabled()) {
			$offer1 = tinypass_create_offer($tp, $postOptions);
		}

		if($tagOptions->isEnabled()) {
			$offer2 = tinypass_create_offer($tp, $tagOptions);
		}

		if($offer1 == null && $offer2 == null)
			return $content;

		if($offer1 == null && $offer2 != null) {
			$offer1 = $offer2;
			$offer2 = null;
		}


		$offerTrialActive1 = false;
		$offerTrialActive2 = false;

		if($offer1->isMetered()) {
			$meter = $tp->getMeterDetails($offer1);
			if($meter->isTrialPeriodActive()) {
				$offerTrialActive1 = true;
			}
		}

		if($offer2 != null && $offer2->isMetered()) {
			$meter = $tp->getMeterDetails($offer2);
			if($meter->isTrialPeriodActive()) {
				$offerTrialActive2 = true;
			}
		}

		//check single offer1
		if($tp->isAccessGranted($offer1) || $offerTrialActive1) {
			return $content . $tp->getWebRequest()->getRequestScript();
		}

		//check offer2
		if($offer2 != null && $tp->isAccessGranted($offer2) || $offerTrialActive2) {
			return $content . $tp->getWebRequest()->getRequestScript();
		}

		//They don't have access
		if(is_page() == false && is_single() == false) {
			return $content . $tp->getWebRequest()->getRequestScript();
		}

		$c = get_extended_with_tpmore($post->post_content);

		if($c['extended'] == '') {
			//means there was no <!--more--> defined
			$content = tinypass_trim_excerpt($content);

		} else {
			$content = ($c['main']);
		}

		$ticket = new TPTicket($offer1, null);

		if($offer2)
			$ticket->setSecondaryOffer($offer2);

		$tinypass_ticket = $ticket;

		return $content .= " [TP_HOOK]";
	}

	return $content;
}

function get_extended_with_tpmore($post) {

	$regex ='/<!--more(.*?)?-->/' ;
	$tpmore_regex ='/<!--tpmore(.*?)?-->/' ;

	if ( preg_match($tpmore_regex, $post)) {
		$regex = $tpmore_regex;
	}

	//Match the new style more links
	if ( preg_match($regex, $post, $matches) ) {
		list($main, $extended) = explode($matches[0], $post, 2);
	} else {
		$main = $post;
		$extended = '';
	}

	// Strip leading and trailing whitespace
	$main = preg_replace('/^[\s]*(.*)[\s]*$/', '\\1', $main);
	$extended = preg_replace('/^[\s]*(.*)[\s]*$/', '\\1', $extended);

	return array('main' => $main, 'extended' => $extended);
}



function tinypass_append_ticket($content) {

	global $tinypass_ticket;
	global $tinypass_instance;
	$ticket = $tinypass_ticket;
	$tp = $tinypass_instance;

	if(!$tinypass_ticket)
		return $content;

	$settings = tinypass_load_settings();

	$buttonHTML = $ticket->createButton();

	$tp->getWebRequest()->setCallback("tinypass_reloader");

	$tp->getWebRequest()->addTicket($ticket);

	$code = $tp->getWebRequest()->getRequestScript();


	$code .= '
			<style type="text/css">
				.tinypass_display {
					text-align: center;
				}
				.tinypass_display .tinypass_access_message {
						font-size: 1.1em;
						margin-bottom: 10px;
					}
			</style>
			<script>
					if(typeof tinypass_reloader != \'function\') {
						function tinypass_reloader(status){
						 if(status.state == \'granted\'){ window.location.reload(); }
						}
					}
			</script>';

	$accessMessage = stripslashes($settings['access_message']);

	if(preg_match('/\[TP_BUTTON\]/', $accessMessage)) {
		$defaultButton .= '<div class="tinypass_display">'
						. preg_replace('/\[TP_BUTTON\]/', $buttonHTML, $accessMessage)
						. ' </div>';
	}else {
		$defaultButton .= '<div class="tinypass_display"> <div class="tinypass_access_message">'
						. stripslashes($settings['access_message']) .'</div> '
						. $buttonHTML
						. ' </div>';
	}

	if(preg_match('/\[TP_HOOK\]/', $content)) {
		$content = preg_replace('/\[TP_HOOK\]/', $defaultButton, $content);
	}else {
		$content .= $defaultButton;
	}

	$content .= $code;

	return $content;
}

function tinypass_create_offer($tp, TinyPassOptions $options) {
	if($options == null)
		return null;
	$resource = new TPResource($options->getResourceId(), $options->getResourceName());

	$pos = array();

	if($options->isEnabled()) {
		for($i = 1; $i <= $options->getNumPrices(); $i++) {

			$po = new TPPriceOption($options->getPrice($i));

			if($options->getAccess($i) != '')
				$po->setAccessPeriod($options->getAccess($i));

			if($options->getCaption($i) != '')
				$po->setCaption($options->getCaption($i));

			if($options->getStartDateSec($i) != '')
				$po->setStartDateInSecs($options->getStartDateSec($i));

			if($options->getEndDateSec($i) != '')
				$po->setEndDateInSecs($options->getEndDateSec($i));

			$pos[] = $po;

		}
	}

	$offer = new TPOffer($resource, $pos);


	if($options->isTimeMetered() ) {

		$offer->addPolicy(TPMeteredPolicy::createReminderByPeriod($options->getTrialPeriod(), $options->getLockoutPeriod()));

	}else if($options->isCountMetered() ) {

		$offer->addPolicy(TPMeteredPolicy::createReminderByAccessCount($options->getMaxAccessAttempts(), $options->getLockoutPeriod()));

	}

	return $offer;

}

function tinypass_trim_excerpt($text) {

	$excerpt_length = apply_filters('excerpt_length', 100);

	$words = preg_split("/[\n\r\t ]+/", $text, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY);
	if ( count($words) > $excerpt_length ) {
		array_pop($words);
		$text = implode(' ', $words);
	} else {
		$text = implode(' ', $words);
	}
	return $text;

}

function tinypass_enabled_tags() {
	global $wpdb;

	$results = false;

	if(WP_CACHE)
		$results = wp_cache_get("tinypass_enabled_tags");

	if($results)
		return $results;

	$terms = array();
	if($results == false) {
		$results = $wpdb->get_results("select * from $wpdb->tinypass_ref ", ARRAY_A);

		foreach($results as $i => $row) {
			$row['data'] = unserialize($row['data']);
			$terms[$row['term_id']] = $row;
		}

		if(WP_CACHE)
			wp_cache_set("tinypass_enabled_tags", $terms);
	}

	return $terms;

}

/**
 * Wrap the TinyPass options in our Options class
 */
function meta_to_tp_options($meta) {
	$options = new TinyPassOptions($meta);
	return $options;
}


/**
 * Options Helper Class used generically across PHP plugins
 */
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

	public function isMetered() {
		if($this->_isset('metered')) {
			return in_array($this->data['metered'], array('count', 'time'));
		}
		return false;
	}

	public function isTimeMetered() {
		return $this->isMetered() && $this->data['metered'] == 'time';
	}

	public function isCountMetered() {
		return $this->isMetered() && $this->data['metered'] == 'count';
	}


	public function getLockoutPeriod() {
		return $this->data["m_lp"] . " " . $this->data["m_lp_type"];
	}

	public function getMaxAccessAttempts() {
		return $this->data["m_maa"];
	}

	public function getTrialPeriod() {
		return $this->data["m_tp"] . " " . $this->data['m_tp_type'];
	}


}




?>
