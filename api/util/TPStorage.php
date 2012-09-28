<?php

class TPStorage {

	function getSiteSettings() {
		$ss = new TPSiteSettings(get_option(TPSiteSettings::TINYPASS_SITE_SETTINGS));
		$ss->addModeSettings(new TPPaySettings(get_option(TPSiteSettings::MODE_STRICT_KEY)));
		$ss->addModeSettings(new TPPaySettings(get_option(TPSiteSettings::MODE_METERED_KEY)));
		return $ss;
	}

	function getPaywalls() {
		$ss = new TPSiteSettings(get_option(TPSiteSettings::TINYPASS_SITE_SETTINGS));
		$ss->addModeSettings(new TPPaySettings(get_option(TPSiteSettings::MODE_STRICT_KEY)));
		$ss->addModeSettings(new TPPaySettings(get_option(TPSiteSettings::MODE_METERED_KEY)));
		return $ss;
	}

	function saveSiteSettings(TPSiteSettings $ss) {
		update_option(TPSiteSettings::TINYPASS_SITE_SETTINGS, $ss->toArray());
		update_option(TPSiteSettings::MODE_STRICT_KEY, $ss->getModeSettings(TPSiteSettings::MODE_STRICT)->toArray());
		update_option(TPSiteSettings::MODE_METERED_KEY, $ss->getModeSettings(TPSiteSettings::MODE_METERED)->toArray());
	}

	function getPostSettings($postID) {
		$meta = get_post_meta($postID, 'tinypass', true);
		return new TPPaySettings($meta);
	}

	function flush() {
		
	}

}

?>