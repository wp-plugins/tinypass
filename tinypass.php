<?php

/*
  Plugin Name: TinyPass
  Plugin URI: http://www.tinypass.com
  Description: Tinypass is the best way to charge for access to content on your WordPress site.  To get started: 1) Click the "Activate" link to the left of this description, 2) Go to http://developer.tinypass.com/main/wordpress and follow the installation instructions to create a free Tinypass publisher account and configure the Tinypass plugin for your WordPress site
  Author: Tinypass
  Version: 2.0.8
  Author URI: http://www.tinypass.com
 */
$tinypass_ppp_req = null;
$tinypass_site_req = null;
$tinypass_meter = null;
$tinypass_add_scripts = false;
$tinypass_embed_appeal = null;
$tinypass_show_appeal = null;

define('TINYPASSS_PLUGIN_PATH', WP_PLUGIN_URL . '/' . str_replace(basename(__FILE__), "", plugin_basename(__FILE__)));

define('TINYPASS_PURCHASE_TEMPLATE', 'tinypass_purchase_display.php');
define('TINYPASS_COUNTER_TEMPLATE', 'tinypass_counter_display.php');
define('TINYPASS_APPEAL_TEMPLATE', 'tinypass_appeal_display.php');

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

//add_filter('the_posts', 'tinypass_the_posts', 5);
add_filter('the_content', 'tinypass_intercept_content', 5);
add_filter('the_content', 'tinypass_append_ticket', 200);
add_action('init', 'tinypass_init');
add_action('wp_footer', 'tinypass_footer');

function tinypass_init() {
  ob_start();
  wp_register_script('tinypass_site', TINYPASSS_PLUGIN_PATH . 'js/tinypass_site.js', array('jquery-ui-dialog'), false, true);
  wp_enqueue_script('tinypass_js', 'http://code.tinypass.com/tinypass.js');
  wp_enqueue_style('tinypass.css', TINYPASSS_PLUGIN_PATH . 'css/tinypass.css');
  wp_enqueue_style('wp-jquery-ui-dialog', TINYPASSS_PLUGIN_PATH . 'css/tinypass.css');
}

/**
 * Footer method to add scripts
 */
function tinypass_footer() {
  global $tinypass_add_scripts;
  global $tinypass_embed_appeal;
  global $tinypass_show_appeal;

  if (!$tinypass_add_scripts)
    return;

  wp_print_scripts('tinypass_site');

  if ($tinypass_embed_appeal)
    echo '<div id="tinypass-appeal-dialog">' . $tinypass_embed_appeal . '</div>';

  if ($tinypass_show_appeal) {
    echo '<script>jQuery(function(){tinypass.showAppeal();})</script>';
  }
}

/**
 * Main function for displaying Tinypass button on restricted post/pages
 */
function tinypass_intercept_content($content) {

  global $tinypass_ppp_req;
  global $tinypass_site_req;
  global $tinypass_meter;
  global $tinypass_add_scripts;
  global $tinypass_embed_appeal;
  global $tinypass_show_appeal;
  global $post;
  $tinypass_ppp_req = null;
  $tinypass_site_req = null;
  $tinypass_meter = null;
  $tinypass_add_scripts = false;
  $tinypass_show_appeal = false;
  $tinypass_embed_appeal = null;

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

  $tinypass_add_scripts = true;

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

  //Only check metered if the mode is metered and it is enabled
  if ($tagOptions->isMode(TPPaySettings::MODE_METERED) && $tagOptions->isEnabled()) {

    $meter = null;
    if ($tagOptions->isMetered()) {

      $cookieName = "TR_" . preg_replace('/[^0-9]*/', '', $tagOptions->getResourceId());
      $meter = TPMeterHelper::loadMeterFromCookie($cookieName, $_COOKIE);


      if ($meter == null) {

        if ($tagOptions->isTimeMetered()) {
          $meter = TPMeterHelper::createTimeBased($cookieName, $tagOptions->getMeterTrialPeriod(), $tagOptions->getMeterLockoutPeriodFull());
        } elseif ($tagOptions->isCountMetered()) {
          $meter = TPMeterHelper::createViewBased($cookieName, $tagOptions->getMeterMaxAccessAttempts(), $tagOptions->getMeterLockoutPeriodFull());
        }
      }

      $meter->increment();

      setcookie($cookieName, TPMeterHelper::__generateLocalToken($cookieName, $meter), time() + 60 * 60 * 24 * 30, '/');

      if ($meter->isTrialPeriodActive()) {
        $tagOfferTrialActive = TRUE;

        if ($tagOptions->isCounterEnabled() && $meter->getTrialViewCount() > $tagOptions->getCounterDelay(PHP_INT_MAX)) {
          $content .= '[TP_COUNTER]';

          $onclick_url = 'onclick="return false"';
          if ($tagOptions->isCounterOnClick(TPPaySettings::CT_ONCLICK_PAGE)) {
            $gotolink = get_page_link($tagOptions->getSubscriptionPageRef());
            $onclick_url = 'href="' . $gotolink . '"';
          } else if ($tagOptions->isCounterOnClick(TPPaySettings::CT_ONCLICK_APPEAL)) {
            $onclick_url = 'onclick="tinypass.showAppeal(); return false"';
            $tinypass_embed_appeal = __tinypass_create_appeal($tagOptions);
          }

          $tinypass_meter = __tinypass_render_template(TINYPASS_COUNTER_TEMPLATE, array(
              'count' => $meter->getTrialViewCount(),
              'max' => $meter->getTrialViewLimit(),
              'remaining' => $meter->getTrialViewLimit() - $meter->getTrialViewCount(),
              'class' => 'position-' . $tagOptions->getCounterPosition(),
              'onclick_url' => $onclick_url,
                  ));
        }

        if ($tagOptions->getAppealEnabled() && $meter != null) {
          $count = $meter->getTrialViewCount();
          if ($count == $tagOptions->getAppealNumViews() ||
                  ( $count > $tagOptions->getAppealNumViews() && $count % $tagOptions->getAppealFrequency() == 0 )) {
            $tinypass_show_appeal = true;
            $tinypass_embed_appeal = __tinypass_create_appeal($tagOptions);
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
    $tinypass_ppp_req = array('req' => $req,
        'message1' => $postOptions->getDeniedMessage1("") != "" ? $postOptions->getDeniedMessage1() : $ss->getDeniedMessage1(),
        'sub1' => $postOptions->getDeniedSub1("") != "" ? $postOptions->getDeniedSub1() : $ss->getDeniedSub1()
    );
    $req->setCallback('tinypass_reloader');
  }

  if ($tagOffer) {
    $req2 = new TPPurchaseRequest($tagOffer, $ticketoptions);
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
 * Append ticket to the end of the post content
 */
function tinypass_append_ticket($content) {

  global $tinypass_ppp_req;
  global $tinypass_site_req;
  global $tinypass_meter;

  //Add the counter
  if (preg_match('/\[TP_COUNTER\]/', $content)) {
    $content = preg_replace('/\[TP_COUNTER\]/', $tinypass_meter, $content);
  }

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

    $request = $tinypass_site_req;
    if ($tinypass_ppp_req != null)
      $request = $tinypass_ppp_req;

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

?>
