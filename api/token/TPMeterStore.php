<?php

class TPMeterStore extends TPAccessTokenStore {


	public function getMeter($rid) {
		return new TPMeter($this->getAccessToken($rid));
	}

	public function getTokens() {
		return parent::getTokens();
	}

	public function loadTokensFromCookie($cookieName = null, $rawCookieString = null) {
		parent::loadTokensFromCookie($cookieName, $rawCookieString);
		parent::_cleanExpiredTokens();
	}


	public function hasToken($rid) {
		return parent::hasToken($rid);
	}

	public function getRawCookie() {
		return parent::getRawCookie();
	}

}
class TPMeterHelper {

	public static function loadMeterFromCookie($meterName, $cookieValue) {
		$store = new TPAccessTokenStore();
		$store->loadTokensFromCookie($cookieValue, $meterName);

		if ($store->hasToken($meterName)) {
			$accessToken = $store->getAccessToken($meterName);
			$meter  = new TPMeter($accessToken);
			if($meter->_isTrialDead())
				return null;
			return $meter;
		} else {
			return null;
		}

	}

	public static function generateCookeEmbedScript($name, $meter) {
		$sb = "";
		$sb.=("<script>");
		$sb.=("document.cookie= '").(self::__generateLocalCookie($name, $meter)).(";");
		$sb.=("path=/;");

		$expires = "expires=' + new Date(new Date().getTime() + 1000*60*60*24*90).toGMTString();";
		if ($meter->isLockoutPeriodActive()) {
			$expires = "expires=" . ($meter->getLockoutEndTimeSecs() + 60) * 1000 + "';";
		}
		//TODO adjust for time and count based - time should end after lockout
		$sb.=($expires);
		$sb.=("</script>");
		return $sb;
	}

	public static function createViewBased($name, $maxViews, $withinPeriod) {
		return TPMeter::createViewBased($name, $maxViews, $withinPeriod);
	}

	public static function createTimeBased($name, $trialPeriod, $lockoutPeriod) {
		return TPMeter::createTimeBased($name, $trialPeriod, $lockoutPeriod);
	}

	public static function __generateLocalToken($name, $meter) {
		$builder = new TPClientBuilder();
		return $builder->buildAccessTokens(new TPAccessToken($meter->getData()));
	}

	public static function __generateLocalCookie($name, $meter) {
		$builder = new TPClientBuilder();
		return $name . "=" . urlencode($builder->buildAccessTokens(new TPAccessToken($meter->getData())));
	}

	

}
?>
