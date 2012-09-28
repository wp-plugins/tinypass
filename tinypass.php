<?php

/*
  Plugin Name: TinyPass
  Plugin URI: http://www.tinypass.com
  Description: TinyPass is the best way to charge for access to content on your WordPress site.  To get started: 1) Click the "Activate" link to the left of this description, 2) Go to http://developer.tinypass.com/main/wordpress and follow the installation instructions to create a free TinyPass publisher account and configure the TinyPass plugin for your WordPress site
  Author: TinyPass
  Version: 2.0.1
  Author URI: http://www.tinypass.com
 */
$tinypass_ppv_req = null;
$tinypass_site_req = null;
define('TINYPASSS_PLUGIN_PATH', WP_PLUGIN_URL . '/' . str_replace(basename(__FILE__), "", plugin_basename(__FILE__)));


register_activation_hook(__FILE__, 'tinypass_activate');
register_deactivation_hook(__FILE__, 'tinypass_deactivate');
register_uninstall_hook(__FILE__, 'tinypass_uninstall');
//setup
if (is_admin()) {
	require_once dirname(__FILE__) . '/tinypass-install.php';
	require_once dirname(__FILE__) . '/tinypass-admin.php';
	require_once dirname(__FILE__) . '/tinypass-form.php';
	include_once dirname(__FILE__) . '/tinymce/plugin.php';
}

add_filter('the_content', 'tinypass_intercept_content', 5);
add_filter('the_content', 'tinypass_append_ticket', 200);
add_action('init', 'tinypass_init');
wp_enqueue_script('tinypass_js', 'http://code.tinypass.com/tinypass.js');
wp_enqueue_style('tinypass.css', TINYPASSS_PLUGIN_PATH . 'css/tinypass.css');

function tinypass_init() {
	ob_start();
}

/**
 * Load and init global tinypass settings
 */
function tinypass_load_settings() {
	$storage = new TPStorage();
	$ss = $storage->getSiteSettings();
	return $ss;
}

/**
 * Main function for displaying TinyPass button on restricted post/pages
 */
function tinypass_intercept_content($content) {

	global $tinypass_ppv_req;
	global $tinypass_site_req;
	global $post;
	$tinypass_ppv_req = null;
	$tinypass_site_req = null;

	tinypass_include();

	//don't process if we aren't on a full single page/post
	if (is_singular() == false)
		return $content;

	//Not a full page
	if (is_page() == false && is_single() == false) {
		return $content;
	}

	$ss = tinypass_load_settings();

	//break out if TinyPass is disabled
	if ($ss->isEnabled() == false)
		return $content;

	$storage = new TPStorage();
	$ppvOptions = $storage->getPostSettings($post->ID);

	$tagOptions = $ss->getActiveSettings();

	TinyPass::$AID = $ss->getAID();
	TinyPass::$PRIVATE_KEY = $ss->getSecretKey();
	TinyPass::$SANDBOX = $ss->isSand();
	//TinyPass::$API_ENDPOINT_DEV = 'http://tinydev.com:9000';

	$store = new TPAccessTokenStore();
	$store->loadTokensFromCookie($_COOKIE);

	//we want to dump the button on this page
	if ($tagOptions->getSubscriptionPageRef() == $post->ID) {
		$siteOffer = TPPaySettings::create_offer($tagOptions, "wp_bundle1");
		$token = $store->getAccessToken("wp_bundle1");

//		if ($token->isAccessGranted()) {
			//wp_redirect(get_page_link($tagOptions->getSubscriptionPageSuccessRef()));
//			$gotolink = get_page_link($tagOptions->getSubscriptionPageSuccessRef());
//			exit;
//		}
		$gotolink = get_page_link($tagOptions->getSubscriptionPageSuccessRef());

		$req = new TPPurchaseRequest($siteOffer);
		$req->setCallback('tinypass_redirect');
		$button1 = $req->generateTag();
		$tinypass_ppv_req = array('req' => $req);

		return $content . "<div id='tinypass_subscription_holder'>$button1</div>"
						. "<script>"
						. "var tp_goto = '$gotolink';"
						. "if(typeof tinypass_redirect != 'function') {
								function tinypass_redirect(status){
								if(status.state == 'granted'){
									window.location = tp_goto;
								}
								}
							}
							if(typeof tpOnPrepare != 'function') {
							function tpOnPrepare(status){
								if(status.state == 'granted'){
									//window.location = tp_goto;
								}
							}
						}" 
						. "</script>";
	}


	$post_terms = wp_get_post_terms($post->ID, 'post_tag', array());
	$tagProtected = false;
	foreach ($post_terms as $term) {
		if ($tagOptions->tagMatches($term->name)) {
			$tagProtected = true;
			break;
		}
	}


	//exit if everything is disabled 
	if ($ppvOptions->isEnabled() == false && $tagProtected == false)
		return $content;

	define('DONOTCACHEPAGE', true);
	define('DONOTCACHEDB', true);
	define('DONOTMINIFY', true);
	define('DONOTCDN', true);
	define('DONOTCACHCEOBJECT', true);

	$ppvOffer = null;
	$siteOffer = null;
	$ppvToken = null;
	$siteToken = null;

	if ($ppvOptions->isEnabled() && $ss->isPPVEnabled()) {
		$ppvOffer = TPPaySettings::create_offer($ppvOptions, "wp_post_" . strval($post->ID), $ppvOptions->getResourceName() == '' ? $post->post_title : $ppvOptions->getResourceName());
		$ppvToken = $store->getAccessToken($ppvOffer->getResource()->getRID());
	}

	$siteOfferTrialActive = FALSE;

	if ($tagProtected) {
		$siteOffer = TPPaySettings::create_offer($tagOptions, "wp_bundle1");
		$siteToken = $store->getAccessToken($siteOffer->getResource()->getRID());

		if ($tagOptions->isMetered()) {

			$cookieName = "TR_S";
			$meter = TPMeterHelper::loadMeterFromCookie($cookieName, $_COOKIE);

			if ($meter == null) {

				if ($tagOptions->isTimeMetered()) {
					$meter = TPMeterHelper::createTimeBased($cookieName, $tagOptions->getMeterTrialPeriod(), $tagOptions->getMeterLockoutPeriodFull());
				} elseif ($tagOptions->isCountMetered()) {
					$meter = TPMeterHelper::createViewBased($cookieName, $tagOptions->getMeterMaxAccessAttempts(), $tagOptions->getMeterLockoutPeriodFull());
				}
			} else {
				$meter->increment();
			}

			setcookie($cookieName, TPMeterHelper::__generateLocalToken($cookieName, $meter), time() + 60 * 60 * 24 * 30, '/');

			if ($meter->isTrialPeriodActive()) {
				$siteOfferTrialActive = TRUE;
			}
		}
	}


	if ($ppvOffer == null && $siteOffer == null)
		return $content;

	//check single offer1
	if ($ppvToken != null && $ppvToken->isAccessGranted()) {
		return $content;
	}

	//check offer2
	if ($siteToken != null && $siteToken->isAccessGranted() || $siteOfferTrialActive) {
		return $content;
	}

	$c = get_extended_with_tpmore($post->post_content);

	if ($c['extended'] == '') {
		//means there was no <!--more--> defined
		$content = tinypass_trim_excerpt($content);
	} else {
		$content = $c['main'];
	}

	$ticketoptions = array();
	if ($ppvOffer) {
		$req = new TPPurchaseRequest($ppvOffer, $ticketoptions);
		$tinypass_ppv_req = array('req' => $req,
				'message1' => $ss->getDeniedMessage1(),
				'sub1' => $ss->getDeniedSub1());
		$req->setCallback('tinypass_reloader');
	}

	if ($siteOffer) {
		$req2 = new TPPurchaseRequest($siteOffer, $ticketoptions);
		$tinypass_site_req = array('req' => $req2,
				'message1' => $tagOptions->getDeniedMessage1(),
				'sub1' => $tagOptions->getDeniedSub1());
		$req2->setCallback('tinypass_reloader');
	}



	return $content .= " [TP_HOOK]";
}

function get_extended_with_tpmore($post) {

	$regex = '/<!--more(.*?)?-->/';
	$tpmore_regex = '/<!--tpmore(.*?)?-->/';

	if (preg_match($tpmore_regex, $post)) {
		$regex = $tpmore_regex;
	}

	//Match the new style more links
	if (preg_match($regex, $post, $matches)) {
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

	global $tinypass_ppv_req;
	global $tinypass_site_req;

	if ($tinypass_ppv_req == null && $tinypass_site_req == null)
		return $content;

	$ss = tinypass_load_settings();

	$ps = $ss->getActiveSettings();

	$tout = '';

	if ($tinypass_ppv_req != null && $tinypass_site_req) {

		$request1 = $tinypass_ppv_req['req'];
		$request2 = $tinypass_site_req['req'];

		$resource1 = $request1->getPrimaryOffer()->getResource()->getName();
		$resource2 = $request2->getPrimaryOffer()->getResource()->getName();

		$button1 = $request1->generateTag();
		$button2 = $request2->generateTag();

		$message1 = stripslashes($tinypass_ppv_req['message1']);
		$message2 = stripslashes($tinypass_site_req['message1']);

		$sub1 = stripslashes($tinypass_ppv_req['sub1']);
		$sub2 = stripslashes($tinypass_site_req['sub1']);

		$tout = __tinypass_render_template('/view/tinypass_display_default.php', array(
				"button1" => $button1,
				"button2" => $button2,
				"message1" => $message1,
				"message2" => $message2,
				"resource1" => $resource1,
				"resource2" => $resource2,
				"sub1" => $sub1,
				"sub2" => $sub2
						));
	} else {

		$request = $tinypass_site_req;
		if ($tinypass_ppv_req != null)
			$request = $tinypass_ppv_req;

		$button1 = $request['req']->generateTag();
		$button2 = '';
		$message1 = stripslashes($request['message1']);
		$sub1 = stripslashes($request['sub1']);

		$resource1 = $request['req']->getPrimaryOffer()->getResource()->getName();

		$tout = __tinypass_render_template('/view/tinypass_display_default.php', array(
				"button1" => $button1,
				"button2" => '',
				"message1" => $message1,
				"sub1" => $sub1,
				"resource1" => $resource1
						));
	}

	if (preg_match('/\[TP_HOOK\]/', $content)) {
		$content = preg_replace('/\[TP_HOOK\]/', $tout, $content);
	}

	return $content;
}

function __tinypass_render_template($template, $vars = array()) {
	foreach ($vars as $name => $value) {
		$$name = $value;
	}

	ob_start();
	require_once dirname(__FILE__) . $template;
	$tout = ob_get_contents();
	ob_end_clean();
	return $tout;
}
/**
 *
 * Trims a string based on WP settings
 *  
 * @param string $text
 * @return string
 */
function tinypass_trim_excerpt($text) {

	$excerpt_length = apply_filters('excerpt_length', 100);

	$words = preg_split("/[\n\r\t ]+/", $text, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY);
	if (count($words) > $excerpt_length) {
		array_pop($words);
		$text = implode(' ', $words);
	} else {
		$text = implode(' ', $words);
	}
	return $text;
}

/**
 * Wrap the TinyPass options in our Options class
 */
function meta_to_tp_options($meta) {
	$options = new TPPaySettings($meta);
	return $options;
}

function tinypass_include() {
	include_once dirname(__FILE__) . '/api/TinyPass.php';
}

function tinypass_debug($obj) {
	echo "<pre>";
	print_r($obj);
	echo "</pre>";
}

?>