<?php

/*
  Plugin Name: TinyPass
  Plugin URI: http://www.tinypass.com
  Description: Tinypass is the best way to charge for access to content on your WordPress site.  To get started: 1) Click the "Activate" link to the left of this description, 2) Go to http://developer.tinypass.com/main/wordpress and follow the installation instructions to create a free Tinypass publisher account and configure the Tinypass plugin for your WordPress site
  Author: Tinypass
  Version: 2.1.0
  Author URI: http://www.tinypass.com
 */

define('TINYPASSS_PLUGIN_PATH', WP_PLUGIN_URL . '/' . str_replace(basename(__FILE__), "", plugin_basename(__FILE__)));
define('TINYPASS_PURCHASE_TEMPLATE', 'tinypass_purchase_display.php');
define('TINYPASS_COUNTER_TEMPLATE', 'tinypass_counter_display.php');
define('TINYPASS_APPEAL_TEMPLATE', 'tinypass_appeal_display.php');

register_activation_hook(__FILE__, 'tinypass_activate');
register_deactivation_hook(__FILE__, 'tinypass_deactivate');
register_uninstall_hook(__FILE__, 'tinypass_uninstall');

class TPState {

	public $post_req = null;
	public $tag_req = null;
	public $meter = null;
	public $add_scripts = false;
	public $embed_appeal = null;
	public $show_appeal = null;

	public function reset() {
		$this->post_req = null;
		$this->tag_req = null;
		$this->meter = null;
		$this->add_scripts = false;
		$this->show_appeal = false;
		$this->embed_appeal = null;
	}

}

$tpstate = new TPState();

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
add_action('wp_footer', 'tinypass_footer');

function tinypass_init() {
	ob_start();
	wp_register_script('tinypass_site', TINYPASSS_PLUGIN_PATH . 'js/tinypass_site.js', array('jquery-ui-dialog'), false, true);
	wp_register_script('tinypass_js', 'http://code.tinypass.com/tinypass.js');
	wp_enqueue_style('tinypass.css', TINYPASSS_PLUGIN_PATH . 'css/tinypass.css');
}

/**
 * Main function for displaying Tinypass button on restricted post/pages
 */
function tinypass_intercept_content($content) {

	global $tpstate;
	global $post;

	$tpstate->reset();

	tinypass_include();

	$ss = tinypass_load_settings();

	//break out if Tinypass is disabled
	if ($ss->isEnabled() == false)
		return $content;

	$storage = new TPStorage();

	$postOptions = $storage->getPostSettings($post->ID);
	$tagOptions = $storage->getPaywallByTag($ss, $post->ID);

	if ($tagOptions->isEnabled() == false)
		$tagOptions = $storage->getPaywallSubRefID($ss, $post->ID);

	TinyPass::$AID = $ss->getAID();
	TinyPass::$PRIVATE_KEY = $ss->getSecretKey();
	TinyPass::$SANDBOX = $ss->isSand();
	//TinyPass::$API_ENDPOINT_DEV = 'http://tinydev.com:9000';

	$store = new TPAccessTokenStore();
	$store->loadTokensFromCookie($_COOKIE);

	//we want to dump the button on this page
	if ($tagOptions->getSubscriptionPageRef() == $post->ID) {
		$tagOffer = TPPaySettings::create_offer($tagOptions, $tagOptions->getResourceId());


//    $token = $store->findActiveToken('/(^wp_bundle1)|(^wp_tag_\d+)/');
//		if ($token->isAccessGranted()) {
		//wp_redirect(get_page_link($tagOptions->getSubscriptionPageSuccessRef()));
//			$gotolink = get_page_link($tagOptions->getSubscriptionPageSuccessRef());
//			exit;
//		}
		$gotolink = get_page_link($tagOptions->getSubscriptionPageSuccessRef());

		$req = new TPPurchaseRequest($tagOffer);
		$req->setCallback('tinypass_redirect');
		$button1 = $req->generateTag();

		if (preg_match('/\[tinypass\s+rid.*\]/', $content)) {
			$content = preg_replace('/\[tinypass\srid+.*\]/', $button1, $content);
			$button1 = '';
		} else {
			$button1 = "<div id='tinypass_subscription_holder'>$button1</div>";
		}

		return $content . $button1
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


	//exit if everything is disabled 
	if ($postOptions->isEnabled() == false && $tagOptions->isEnabled() == false)
		return $content;

	$tpstate->add_scripts = true;

	//When content is shown in list form, i.e. categories we still need to truncate content
	//At this point in the execution we know that TP is enabled so we have to protect
	if (is_singular() == false) {
		$c = get_extended_with_tpmore($content);
		if ($c['extended'] == '') {
			$content = tinypass_trim_excerpt($content);
		} else {
			$content = $c['main'];
		}
		return $content;
	}

	$post->comment_status = "closed";

	define('DONOTCACHEPAGE', true);
	define('DONOTCACHEDB', true);
	define('DONOTMINIFY', true);
	define('DONOTCDN', true);
	define('DONOTCACHCEOBJECT', true);

	$postOffer = null;
	$tagOffer = null;
	$postToken = null;
	$tagToken = null;

	if ($postOptions->isEnabled() && $ss->isPPPEnabled()) {
		$postOffer = TPPaySettings::create_offer($postOptions, "wp_post_" . strval($post->ID), $postOptions->getResourceName() == '' ? $post->post_title : $postOptions->getResourceName());
		$postToken = $store->getAccessToken($postOffer->getResource()->getRID());
	}


	$tagOfferTrialActive = FALSE;

	if ($tagOptions != null && $tagOptions->isEnabled()) {
		$tagOffer = TPPaySettings::create_offer($tagOptions, $tagOptions->getResourceId());
		$tagToken = $store->findActiveToken('/' . $tagOptions->getResourceId() . '/');
	}

	//For PPV mode
	if ($tagOptions->isMode(TPPaySettings::MODE_PPV) && $tagOptions->isEnabled()) {

		$tagOffer = TPPaySettings::create_offer($tagOptions, "wp_post_" . strval($post->ID), $post->post_title);
		//If a offer on the post is defined then use that one
		if ($postOffer != null) {
			$tagOffer = null;
		}
	} else if ($tagOptions->isMode(TPPaySettings::MODE_METERED) && $tagOptions->isEnabled()) {
		//Only check metered if the mode is metered and it is enabled

		$meter = null;
		if ($tagOptions->isMetered()) {

			$cookieName = "tr_" . substr(md5($tagOptions->getResourceId()), 0, 20);
			$meter = TPMeterHelper::loadMeterFromCookie($cookieName, $_COOKIE);

			$lockoutPeriod = $tagOptions->getMeterLockoutPeriodFull();
			if ($meter == null) {
				$meter = TPMeterHelper::createViewBased($cookieName, $tagOptions->getMeterMaxAccessAttempts(), $lockoutPeriod);
			}

			$lockoutPeriodEndTime = time() + TPUtils::parseLoosePeriodInSecs($lockoutPeriod);

			$meter->increment();

			setcookie($cookieName, TPMeterHelper::__generateLocalToken($cookieName, $meter), $lockoutPeriodEndTime, '/');

			if ($meter->isTrialPeriodActive()) {
				$tagOfferTrialActive = TRUE;

				if ($tagOptions->isCounterEnabled() && $meter->getTrialViewCount() > $tagOptions->getCounterDelay(PHP_INT_MAX)) {
					$content .= '[TP_COUNTER]';

					$onclick = 'onclick="return false"';
					if ($tagOptions->isCounterOnClick(TPPaySettings::CT_ONCLICK_PAGE)) {
						$gotolink = get_page_link($tagOptions->getSubscriptionPageRef());
						$onclick = 'href="' . $gotolink . '"';
					} else if ($tagOptions->isCounterOnClick(TPPaySettings::CT_ONCLICK_APPEAL)) {
						$onclick = 'onclick="tinypass.showAppeal(); return false"';
						$tpstate->embed_appeal = __tinypass_create_appeal($tagOptions);
					}

					$tpstate->meter = __tinypass_render_template(TINYPASS_COUNTER_TEMPLATE, array(
							'count' => $meter->getTrialViewCount(),
							'max' => $meter->getTrialViewLimit(),
							'remaining' => $meter->getTrialViewLimit() - $meter->getTrialViewCount(),
							'position' => 'position-' . $tagOptions->getCounterPosition(),
							'onclick' => $onclick,
									));
				}

				if ($tagOptions->getAppealEnabled() && $meter != null) {
					$count = $meter->getTrialViewCount();
					if ($count == $tagOptions->getAppealNumViews() ||
									( $count > $tagOptions->getAppealNumViews() && $count % $tagOptions->getAppealFrequency() == 0 )) {
						$tpstate->show_appeal = true;
						$tpstate->embed_appeal = __tinypass_create_appeal($tagOptions);
					}
				}

				return $content;
			}
		}
	}

	if ($postOffer == null && $tagOffer == null)
		return $content;

	//check single offer1
	if ($postToken != null && $postToken->isAccessGranted()) {
		return $content;
	}

	//check offer2
	if ($tagToken != null && $tagToken->isAccessGranted() || $tagOfferTrialActive) {
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
	if ($postOffer) {
		$req = new TPPurchaseRequest($postOffer, $ticketoptions);
		$tpstate->post_req = array('req' => $req,
				'message1' => $postOptions->getDeniedMessage1("") != "" ? $postOptions->getDeniedMessage1() : $ss->getDeniedMessage1(),
				'sub1' => $postOptions->getDeniedSub1("") != "" ? $postOptions->getDeniedSub1() : $ss->getDeniedSub1()
		);
		$req->setCallback('tinypass_reloader');
	}

	if ($tagOffer) {
		$req2 = new TPPurchaseRequest($tagOffer, $ticketoptions);
		$tpstate->tag_req = array('req' => $req2,
				'message1' => $tagOptions->getDeniedMessage1(),
				'sub1' => $tagOptions->getDeniedSub1()
		);
		$req2->setCallback('tinypass_reloader');
	}

	if ($tagOptions->isPostFirstInOrder() == false) {
		$temp = $tpstate->post_req;
		$tpstate->post_req = $tpstate->tag_req;
		$tpstate->tag_req = $temp;
	}

	return $content .= " [TP_HOOK]";
}

/**
 * Append ticket to the end of the post content
 */
function tinypass_append_ticket($content) {

	global $tpstate;

	//Add the counter
	if (preg_match('/\[TP_COUNTER\]/', $content)) {
		$content = preg_replace('/\[TP_COUNTER\]/', $tpstate->meter, $content);
	}

	if ($tpstate->post_req == null && $tpstate->tag_req == null)
		return $content;

	$tout = '';

	if ($tpstate->post_req != null && $tpstate->tag_req) {

		$request1 = $tpstate->post_req['req'];
		$request2 = $tpstate->tag_req['req'];

		$resource1 = $request1->getPrimaryOffer()->getResource()->getName();
		$resource2 = $request2->getPrimaryOffer()->getResource()->getName();

		$button1 = $request1->generateTag();
		$button2 = $request2->generateTag();

		$message1 = stripslashes($tpstate->post_req['message1']);
		$message2 = stripslashes($tpstate->tag_req['message1']);

		$sub1 = stripslashes($tpstate->post_req['sub1']);
		$sub2 = stripslashes($tpstate->tag_req['sub1']);

		$tout = __tinypass_render_template(TINYPASS_PURCHASE_TEMPLATE, array(
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

		$request = $tpstate->tag_req;
		if ($tpstate->post_req != null)
			$request = $tpstate->post_req;

		$button1 = $request['req']->generateTag();
		$button2 = '';
		$message1 = stripslashes($request['message1']);
		$sub1 = stripslashes($request['sub1']);

		$resource1 = $request['req']->getPrimaryOffer()->getResource()->getName();

		$tout = __tinypass_render_template(TINYPASS_PURCHASE_TEMPLATE, array(
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

/**
 * Internal function for rendering templates
 */
function __tinypass_render_template($template, $vars = array()) {

	if (file_exists(get_template_directory() . '/' . $template)) {
		$filename = get_template_directory() . '/' . $template;
	} else {
		$filename = dirname(__FILE__) . '/view/' . $template;
	}

	foreach ($vars as $name => $value) {
		$$name = $value;
	}

	ob_start();
	require $filename;
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

	//$text = wp_strip_all_tags($text);

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
 * Include the TP api files
 */
function tinypass_include() {
	include_once dirname(__FILE__) . '/api/TinyPass.php';
}

/**
 * Debug helper
 */
function tinypass_debug($obj) {
	echo "<pre>";
	print_r($obj);
	echo "</pre>";
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
 * Split the content by more or tp more
 */
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

function __tinypass_create_appeal($tagOptions) {
	return __tinypass_render_template(TINYPASS_APPEAL_TEMPLATE, array(
							'header' => $tagOptions->getAppealMessage1('Purchase to get full access to our great content'),
							'body' => $tagOptions->getAppealMessage2('Pay in under a minute with Tinypass.  Use your credit card, Paypal, or Google Wallet'),
							'link' => get_page_link($tagOptions->getSubscriptionPageRef())
									)
	);
}

/**
 * Footer method to add scripts
 */
function tinypass_footer() {
	global $tpstate;

	if (!$tpstate->add_scripts)
		return;

	wp_print_scripts('tinypass_js');
	wp_print_scripts('tinypass_site');

	if ($tpstate->embed_appeal)
		echo '<div id="tinypass-appeal-dialog">' . $tpstate->embed_appeal . '</div>';

	if ($tpstate->show_appeal) {
		echo '<script>jQuery(function(){tinypass.showAppeal();})</script>';
	}
}

?>