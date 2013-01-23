<?php

/*
  Plugin Name: TinyPass:Metered
  Plugin URI: http://www.tinypass.com
  Description: Tinypass is the best way to charge for access to content on your WordPress site.  To get started: 1) Click the "Activate" link to the left of this description, 2) Go to http://developer.tinypass.com/main/wordpress and follow the installation instructions to create a free Tinypass publisher account and configure the Tinypass plugin for your WordPress site
  Author: Tinypass
  Version: 1.0.0
  Author URI: http://www.tinypass.com
 */

define('TINYPASSS_PLUGIN_PATH', WP_PLUGIN_URL . '/' . str_replace(basename(__FILE__), "", plugin_basename(__FILE__)));

register_activation_hook(__FILE__, 'tinypass_activate');
register_deactivation_hook(__FILE__, 'tinypass_deactivate');
register_uninstall_hook(__FILE__, 'tinypass_uninstall');

class TPMeterState {

	public $embed_meter = null;
	public $do_not_track = false;
	public $paywall_id = 0;

	public function reset() {
		$this->embed_meter = null;
		$this->do_not_track = false;
		$this->paywall_id = 0;
	}

}

$tpmeter = new TPMeterState();

//setup
if (is_admin()) {
	require_once dirname(__FILE__) . '/tinypass-install.php';
	require_once dirname(__FILE__) . '/tinypass-admin.php';
	require_once dirname(__FILE__) . '/tinypass-form.php';
	include_once dirname(__FILE__) . '/tinymce/plugin.php';
}

add_filter('the_content', 'tinypass_intercept_content', 5);
add_action('init', 'tinypass_init');
add_action('wp_footer', 'tinypass_footer');

function tinypass_init() {
	ob_start();
	wp_enqueue_style('tinypass.css', TINYPASSS_PLUGIN_PATH . 'css/tinypass.css');
}

/**
 * This method performs nearly all of the TinyPass logic for when and how to protect content.
 * Based upon the TP configuration, the post, the tags this method will either permit access
 * to a post or it will truncate the content and show a 'purchase now' widget instead of the post content.
 * 
 * Access is checked by retreiving an encrypted cookie that is stored after a successful purchase.
 * 
 */
function tinypass_intercept_content($content) {

	global $tpmeter;
	global $post;

	tinypass_include();

	$ss = tinypass_load_settings();

	//break out if Tinypass is disabled
	if ($ss->isEnabled() == false)
		return $content;

	$storage = new TPStorage();

	$tagOptions = $storage->getPaywall("pw_config");

	//NOOP if pw is disabled or the wrong mode
	if ($tagOptions->isEnabled() == false || $tagOptions->isMode(TPPaySettings::MODE_METERED_LIGHT) == false)
		return $content;

	if (is_home()) {
		$tpmeter->do_not_track = !$tagOptions->isTrackHomePage();
	} else {
		//check if current post is tagged for restriction
		$post_terms = wp_get_post_terms($post->ID, 'post_tag', array());
		foreach ($post_terms as $term) {
			if ($tagOptions->tagMatches($term->name)) {
				$tpmeter->do_not_track = false;
			}
		}
	}

	$tpmeter->embed_meter = true;
	$tpmeter->paywall_id = $tagOptions->getPaywallID($ss->isProd());


	//When content is shown in list form, i.e. categories we still need to truncate content
	//At this point in the execution we know that TP is enabled so we have to protect
	/*
	  if (is_singular() == false) {
	  $c = get_extended_with_tpmore($content);
	  if ($c['extended'] == '') {
	  $content = tinypass_trim_excerpt($content);
	  } else {
	  $content = $c['main'];
	  }
	  return $content;
	  }
	 */

	if (is_home()) {
		$c = get_extended_with_tpmore($post->post_content);

		if ($c['extended'] == '') {
			$content = tinypass_trim_excerpt($content);
			$content .= "<div>some div</div>";
		} else {
			$content = $c['main'];
			$content .= apply_filters('the_content_more_link', ' <a href="' . get_permalink() . "\" class=\"readon-link\">Read On</a>", 'Read On');
		}
	}



	return $content;
}

/**
 * Trims a string based on WP settings
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
 * Helper method to include tinypass related files
 */
function tinypass_include() {
	include_once dirname(__FILE__) . '/util/TPStorage.php';
	include_once dirname(__FILE__) . '/util/TPPaySettings.php';
	include_once dirname(__FILE__) . '/util/TPSiteSettings.php';
	include_once dirname(__FILE__) . '/util/TPValidate.php';
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

/**
 * Footer method to add scripts
 */
function tinypass_footer() {
	global $tpmeter;

	if ($tpmeter->embed_meter) {
		echo "
<script type=\"text/javascript\">
    window._tpm = window._tpm || [];
    window._tpm['paywallID'] = '" . $tpmeter->paywall_id . "'; 
    window._tpm['paywallID'] = '" . 8010 . "'; 
    window._tpm['jquery_trackable_selector'] = '.readon-link';
    window._tpm['sandbox'] = 'true';
    window._tpm['doNotTrack'] = " . ($tpmeter->do_not_track ? 'true' : 'false') . " 
    window._tpm['host'] = 'tinydev.com:9000';
    window._tpm['host'] = 'sandbox.tinypass.com';

		 (function () {
        var _tp = document.createElement('script');
        _tp.type = 'text/javascript';
        var _host = window._tpm['host'] ? window._tpm['host'] : 'code.tinypass.com';
        _tp.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + _host + '/tinypass-meter.js';
        var s = document.getElementsByTagName('script')[0];
        s.parentNode.insertBefore(_tp, s);
    })();

</script>\n\n";
	}
}

?>