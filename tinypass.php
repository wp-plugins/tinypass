<?php

/*
  Plugin Name: TinyPass:Metered
  Plugin URI: http://www.tinypass.com
  Description: TinyPass:Metered allows for metered access to your WordPress site
  Author: Tinypass
  Version: 1.0.0
  Author URI: http://www.tinypass.com
 */

define('TINYPASSS_PLUGIN_PATH', WP_PLUGIN_URL . '/' . str_replace(basename(__FILE__), "", plugin_basename(__FILE__)));

register_activation_hook(__FILE__, 'tinypass_activate');
register_deactivation_hook(__FILE__, 'tinypass_deactivate');
register_uninstall_hook(__FILE__, 'tinypass_uninstall');

wp_enqueue_script('tp-util', TINYPASSS_PLUGIN_PATH . 'js/tp-util.js', array(), true, true);

if (!class_exists('TPMeterState')) {

	class TPMeterState {

		public $embed_meter = null;
		public $do_not_track = false;
		public $paywall_id = 0;
		public $sandbox = 0;

		public function reset() {
			$this->embed_meter = null;
			$this->do_not_track = false;
			$this->paywall_id = 0;
			$this->sandbox = 0;
		}

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

add_action('init', 'tinypass_init');
add_action('wp_footer', 'tinypass_footer');

function tinypass_init() {
	global $more;
	ob_start();

	if (is_admin())
		wp_enqueue_style('tinypass.css', TINYPASSS_PLUGIN_PATH . 'css/tinypass.css');

	if (tinypass_is_readon_request()) {
		$id = $_REQUEST['p'];
		$query = new WP_Query(array('post_type' => 'any', 'p' => $id));

		if (!$query->have_posts()) {
			header("HTTP/1.0 404 Not Found");
			exit;
		}

		$post = $query->the_post();

		$more = true;
		$content = get_the_content("");

		$content = apply_filters('the_content', $content);
		$content = str_replace(']]>', ']]&gt;', $content);

		$c = tinypass_split_excerpt_and_body($content, false);

		$content = $c['extended'];

		echo $content;
		exit;
	}

	add_filter('the_content', 'tinypass_intercept_content', 5);
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


	if (tinypass_is_readon_request())
		return $content;

	tinypass_include();

	$ss = tinypass_load_settings();

	//break out if Tinypass is disabled
	if ($ss->isEnabled() == false)
		return $content;

	$storage = new TPStorage();

	$pwOptions = $storage->getPaywall("pw_config");

	//NOOP if pw is disabled or the wrong mode
	if ($pwOptions->isEnabled() == false || $pwOptions->isMode(TPPaySettings::MODE_METERED_LIGHT) == false)
		return $content;

	if (is_home()) {
		$tpmeter->do_not_track = !$pwOptions->isTrackHomePage();
	} else {
		//check if current post is tagged for restriction
		$post_terms = wp_get_post_terms($post->ID, 'post_tag', array());
		foreach ($post_terms as $term) {
			if ($pwOptions->tagMatches($term->name)) {
				$tpmeter->do_not_track = false;
			}
		}
	}

	$tpmeter->embed_meter = true;
	$tpmeter->paywall_id = $pwOptions->getPaywallID($ss->isProd());
	$tpmeter->sandbox = $ss->isSand();

	if (is_home() && ($pwOptions->isReadOnEnabled())) {
		$c = tinypass_split_excerpt_and_body($post->post_content, false);

		$content = $c['main'];

		//we only want to show if there is a readmore or tpmore
		if ($c['extended'] && $c['extended'] != "") {
			$url = get_permalink();
			$rurl = $url . "?readon=fetch";
			if (preg_match("/\?/", $url))
				$rurl = $url . "&readon=fetch";

			$id = hash('md5', $url);
			$content .= '<div id="' . $id . '" class="extended" style="display:none"></div>';
			$content .= apply_filters('the_content_more_link', '<a href="' . get_permalink() . "\" longdesc=\"Read On\" rid=\"$id\" rurl=\"$rurl\" class=\"readon-link\">Read On</a>", 'Read On');
		}
	} else if (is_singular()) {

		$c = tinypass_split_excerpt_and_body($post->post_content);
		$content = $c['main'] . "<br>" . $c['extended'];
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

function tinypass_is_readon_request() {
	$result = false;
	$header = $_SERVER['HTTP_X_REQUESTED_WITH'];
	if ($header && strtolower($header) == 'xmlhttprequest') {
		if ($_REQUEST['readon'] == 'fetch') {
			$result = true;
		}
	}
	return $result;
}

/**
 * Split the content by more or tp more
 */
function tinypass_split_excerpt_and_body($post, $surround = true) {

	$regex = '/<!--more(.*?)?-->|<span id="(.*)"><\/span>/';
	$tpmore_regex = '/\s*<!--tpmore(.*?)?-->\s*/';

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
	if ($surround)
		$extended = preg_replace('/^[\s]*(.*)[\s]*$/', '\\1', "<div id='tpmore'>" . $extended . "</div>");
	else
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
    window._tpm['jquery_trackable_selector'] = '.readon-link';
    window._tpm['sandbox'] = " . ($tpmeter->sandbox ? 'true' : 'false') . " 
    window._tpm['doNotTrack'] = " . ($tpmeter->do_not_track ? 'true' : 'false') . "; 
    window._tpm['host'] = 'tinydev.com:9000';
	
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