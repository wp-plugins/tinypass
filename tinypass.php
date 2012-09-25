<?php

/*
  Plugin Name: TinyPass
  Plugin URI: http://www.tinypass.com
  Description: TinyPass is the best way to charge for access to content on your WordPress site.  To get started: 1) Click the "Activate" link to the left of this description, 2) Go to http://developer.tinypass.com/main/wordpress and follow the installation instructions to create a free TinyPass publisher account and configure the TinyPass plugin for your WordPress site
  Author: TinyPass
  Version: 1.4.16
  Author URI: http://www.tinypass.com
 */

$tinypass_request = null;
define('TINYPASSS_PLUGIN_PATH', WP_PLUGIN_URL . '/' . str_replace(basename(__FILE__), "", plugin_basename(__FILE__)));

//setup
if (is_admin()) {
	require_once dirname(__FILE__) . '/tinypass-install.php';
	require_once dirname(__FILE__) . '/tinypass-admin.php';
	require_once dirname(__FILE__) . '/tinypass-form.php';
	include_once dirname(__FILE__) . '/tinymce/plugin.php';
}


add_filter('the_content', 'tinypass_intercept_content', 5);
add_filter('the_content', 'tinypass_append_ticket', 200);
wp_enqueue_script('tinypass_js', 'http://code.tinypass.com/tinypass.js');
wp_enqueue_style('tinypass.css', TINYPASSS_PLUGIN_PATH . 'css/tinypass.css');

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

	global $tinypass_request;
	global $post;
	$tinypass_request = null;

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
	$postOptions = $storage->getPostSettings($post->ID);

	$siteOptions = $ss->getActiveSettings();

	TinyPass::$AID = $ss->getAID();
	TinyPass::$PRIVATE_KEY = $ss->getSecretKey();
	TinyPass::$SANDBOX = $ss->isSand();
	//TinyPass::$API_ENDPOINT_DEV = 'http://tinydev.com:9000';

	$store = new TPAccessTokenStore();
	$store->loadTokensFromCookie($_COOKIE);

	//we want to dump the button on this page
	if ($siteOptions->getSubscriptionPageRef() == $post->ID) {
		$secondaryOffer = tinypass_create_offer($siteOptions, "wp_site");
		$req = new TPPurchaseRequest($secondaryOffer);
		$button1 = $req->generateTag();

		$tinypass_request = $req;

		return $content . "<div id='tinypass_subscription_holder'>$button1</div>";
	}


	$post_terms = wp_get_post_terms($post->ID, 'post_tag', array());
	$tagProtected = false;
	if ($siteOptions->isEnabledPerTag()) {
		foreach ($post_terms as $term) {
			if ($siteOptions->tagMatches($term->name)) {
				$tagProtected = true;
				break;
			}
		}
	}

	//exit if everything is disabled 
	if ($postOptions->isEnabled() == false && $tagProtected == false)
		return $content;

	define('DONOTCACHEPAGE', true);
	define('DONOTCACHEDB', true);
	define('DONOTMINIFY', true);
	define('DONOTCDN', true);
	define('DONOTCACHCEOBJECT', true);

	$primaryOffer = null;
	$secondaryOffer = null;
	$primaryToken = null;
	$secondaryToken = null;

	if ($postOptions->isEnabled() && $siteOptions->isEnabledPerPost()) {
		$primaryOffer = tinypass_create_offer($postOptions, "wp_post_" . strval($post->ID), $postOptions->getResourceName() == '' ? $post->post_title : $postOptions->getResourceName());
		$primaryToken = $store->getAccessToken($primaryOffer->getResource()->getRID());
	}


	if ($tagProtected) {
		$secondaryOffer = tinypass_create_offer($siteOptions, "wp_site");
		$secondaryToken = $store->getAccessToken($secondaryOffer->getResource()->getRID());

		/*
		  if ($tagOptions->isMetered()) {

		  //TODO
		  $cookieName = "TR_" . $node->tinypass->tag_meta['tid'];
		  $meter = TPMeterHelper::loadMeterFromCookie($cookieName, $_COOKIE);

		  if ($meter == null) {


		  if ($tp_options->isTimeMetered()) {
		  $meter = TPMeterHelper::createTimeBased($cookieName, $tp_options->getTrialPeriod(), $tp_options->getLockoutPeriod());
		  } elseif ($tp_options->isCountMetered()) {
		  $meter = TPMeterHelper::createViewBased($cookieName, $tp_options->getMaxAccessAttempts(), $tp_options->getLockoutPeriod());
		  }
		  } else {
		  $meter->increment();
		  }

		  $domain = variable_get('site_name', "localhost");
		  setcookie($cookieName, TPMeterHelper::__generateLocalToken($cookieName, $meter), time() + 60 * 60 * 24 * 30, '/', $domain);

		  if ($meter->isTrialPeriodActive()) {
		  $secondaryOfferTrialActive = TRUE;
		  }
		  }
		 */
	}

	$primaryOfferTrialActive = FALSE;
	$secondaryOfferTrialActive = FALSE;

	if ($primaryOffer == null && $secondaryOffer == null)
		return $content;

	if ($primaryOffer == null && $secondaryOffer != null) {
		$primaryOffer = $secondaryOffer;
		$primaryToken = $secondaryToken;
		$primaryOfferTrialActive = $secondaryOfferTrialActive;
		$secondaryOffer = null;
		$secondaryToken = null;
		$secondaryOfferTrialActive = FALSE;
	}


	//check single offer1
	if ($primaryToken->isAccessGranted() || $primaryOfferTrialActive) {
		return $content;
	}

	//check offer2
	if ($secondaryToken != null && $secondaryToken->isAccessGranted() || $secondaryOfferTrialActive) {
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
	$req = new TPPurchaseRequest($primaryOffer, $ticketoptions);

	if ($secondaryOffer)
		$req->setSecondaryOffer($secondaryOffer);

	$req->setCallback('tinypass_reloader');

	$tinypass_request = $req;

	return $content .= " [TP_HOOK]";
	//}
//	return $content;
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

	global $tinypass_request;
	$request1 = $tinypass_request;

	if (!$tinypass_request)
		return $content;

	$settings = tinypass_load_settings();

	$ps = $settings->getActiveSettings();

	$tout = '';

	if ($ps->isPaymentDisplayExpanded() && $request1->getSecondaryOffer() != null) {

		$resource1 = $request1->getPrimaryOffer()->getResource()->getName();
		$resource2 = $request1->getSecondaryOffer()->getResource()->getName();

		$request2 = new TPPurchaseRequest($request1->getSecondaryOffer(), null);
		$button2 = $request2->generateTag();

		$request1->setSecondaryOffer(null);
		$button1 = $request1->generateTag();

		$message1 = stripslashes($ps->getDeniedMessage1());
		$message2 = stripslashes($ps->getDeniedMessage2());

		$sub1 = stripslashes($ps->getDeniedSub1());
		$sub2 = stripslashes($ps->getDeniedSub2());

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

		$button1 = $request1->generateTag();
		$button2 = '';
		$message1 = stripslashes($ps->getDeniedMessage1());
		$sub1 = stripslashes($ps->getDeniedSub1());

		$resource1 = $request1->getPrimaryOffer()->getResource()->getName();

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
 * Create offer from settings data
 *  
 * @param TPPaySettings $ps
 * @return returns null or a valid TPOffer
 */
function tinypass_create_offer(&$ps, $rid, $rname = null) {
	if ($ps == null)
		return null;

	if ($rname == '' || $rname == null)
		$rname = $ps->getResourceName();

	$resource = new TPResource($rid, $rname);

	$pos = array();

	for ($i = 1; $i <= $ps->getNumPrices(); $i++) {

		$po = new TPPriceOption($ps->getPrice($i));

		if ($ps->getAccess($i) != '')
			$po->setAccessPeriod($ps->getAccess($i));

		if ($ps->getCaption($i) != '')
			$po->setCaption($ps->getCaption($i));

		if ($ps->getRecurring($i) != '')
			$po->setRecurringBilling($ps->getRecurring($i));


		$pos[] = $po;
	}

	$offer = new TPOffer($resource, $pos);

	/*
	  if ($ps->isTimeMetered()) {

	  $offer->addPolicy(TPMeteredPolicy::createReminderByPeriod($ps->getTrialPeriod(), $ps->getLockoutPeriod()));
	  } else if ($ps->isCountMetered()) {

	  $offer->addPolicy(TPMeteredPolicy::createReminderByAccessCount($ps->getMaxAccessAttempts(), $ps->getLockoutPeriod()));
	  }
	 */

	return $offer;
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
