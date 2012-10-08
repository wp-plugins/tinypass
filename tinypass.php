<?php

/*
  Plugin Name: TinyPass
  Plugin URI: http://www.tinypass.com
  Description: Tinypass is the best way to charge for access to content on your WordPress site.  To get started: 1) Click the "Activate" link to the left of this description, 2) Go to http://developer.tinypass.com/main/wordpress and follow the installation instructions to create a free Tinypass publisher account and configure the Tinypass plugin for your WordPress site
  Author: Tinypass
  Version: 2.0.7
  Author URI: http://www.tinypass.com
 */
$tinypass_ppp_req = null;
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

/**
 * Main function for displaying Tinypass button on restricted post/pages
 */
function tinypass_intercept_content($content) {

  global $tinypass_ppp_req;
  global $tinypass_site_req;
  global $post;
  $tinypass_ppp_req = null;
  $tinypass_site_req = null;

  tinypass_include();

  $ss = tinypass_load_settings();

  //break out if Tinypass is disabled
  if ($ss->isEnabled() == false)
    return $content;


  $storage = new TPStorage();
  $ppvOptions = $storage->getPostSettings($post->ID);

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
    $siteOffer = TPPaySettings::create_offer($tagOptions, "wp_bundle1");

//    $token = $store->findActiveToken('/(^wp_bundle1)|(^wp_tag_\d+)/');
//		if ($token->isAccessGranted()) {
    //wp_redirect(get_page_link($tagOptions->getSubscriptionPageSuccessRef()));
//			$gotolink = get_page_link($tagOptions->getSubscriptionPageSuccessRef());
//			exit;
//		}
    $gotolink = get_page_link($tagOptions->getSubscriptionPageSuccessRef());

    $req = new TPPurchaseRequest($siteOffer);
    $req->setCallback('tinypass_redirect');
    $button1 = $req->generateTag();

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


  //exit if everything is disabled 
  if ($ppvOptions->isEnabled() == false && $tagOptions->isEnabled() == false)
    return $content;

  //When content is shown in list form, i.e. categories we still need to truncate content
  //At this point in the execution we know that TP is enabled so we have to protect
  if(is_singular() == false){
    $c = get_extended_with_tpmore($content);
    if($c['extended'] == '') {
      $content = tinypass_trim_excerpt ($content);
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

  $pppOffer = null;
  $siteOffer = null;
  $pppToken = null;
  $siteToken = null;

  if ($ppvOptions->isEnabled() && $ss->isPPVEnabled()) {
    $pppOffer = TPPaySettings::create_offer($ppvOptions, "wp_post_" . strval($post->ID), $ppvOptions->getResourceName() == '' ? $post->post_title : $ppvOptions->getResourceName());
    $pppToken = $store->getAccessToken($pppOffer->getResource()->getRID());
  }

  $siteOfferTrialActive = FALSE;

  if ($tagOptions->isEnabled()) {
    $siteOffer = TPPaySettings::create_offer($tagOptions, "wp_bundle1");

    $siteToken = $store->findActiveToken('/(^wp_bundle1)|(^wp_tag_\d+)/');

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


  if ($pppOffer == null && $siteOffer == null)
    return $content;

  //check single offer1
  if ($pppToken != null && $pppToken->isAccessGranted()) {
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
  if ($pppOffer) {
    $req = new TPPurchaseRequest($pppOffer, $ticketoptions);
    $tinypass_ppp_req = array('req' => $req,
        'message1' => $ppvOptions->getDeniedMessage1("") != "" ? $ppvOptions->getDeniedMessage1() : $ss->getDeniedMessage1(),
        'sub1' => $ppvOptions->getDeniedSub1("") != "" ? $ppvOptions->getDeniedSub1() : $ss->getDeniedSub1()
    );
    $req->setCallback('tinypass_reloader');
  }

  if ($siteOffer) {
    $req2 = new TPPurchaseRequest($siteOffer, $ticketoptions);
    $tinypass_site_req = array('req' => $req2,
        'message1' => $tagOptions->getDeniedMessage1(),
        'sub1' => $tagOptions->getDeniedSub1()
    );
    $req2->setCallback('tinypass_reloader');
  }

  if ($tagOptions->isPostFirstInOrder() == false) {
    $temp = $tinypass_ppp_req;
    $tinypass_ppp_req = $tinypass_site_req;
    $tinypass_site_req = $temp;
  }

  return $content .= " [TP_HOOK]";
}

/**
 * 
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

function tinypass_append_ticket($content) {

  global $tinypass_ppp_req;
  global $tinypass_site_req;

  if ($tinypass_ppp_req == null && $tinypass_site_req == null)
    return $content;

  $tout = '';

  if ($tinypass_ppp_req != null && $tinypass_site_req) {

    $request1 = $tinypass_ppp_req['req'];
    $request2 = $tinypass_site_req['req'];

    $resource1 = $request1->getPrimaryOffer()->getResource()->getName();
    $resource2 = $request2->getPrimaryOffer()->getResource()->getName();

    $button1 = $request1->generateTag();
    $button2 = $request2->generateTag();

    $message1 = stripslashes($tinypass_ppp_req['message1']);
    $message2 = stripslashes($tinypass_site_req['message1']);

    $sub1 = stripslashes($tinypass_ppp_req['sub1']);
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
    if ($tinypass_ppp_req != null)
      $request = $tinypass_ppp_req;

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
  require dirname(__FILE__) . $template;
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
 * Wrap the Tinypass options in our Options class
 */
function meta_to_tp_options($meta) {
  $options = new TPPaySettings($meta);
  return $options;
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
 *  Init buffer so that we can send out a cookie in the rsp header
 */
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

?>
