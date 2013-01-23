<?php

/**
 * Redirects to the edit paywall page
 */
function tinypass_paywalls_list() {

	if (true)
		wp_redirect(menu_page_url("TinyPassEditPaywall") . "&rid=pw_config");
}
?>