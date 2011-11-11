<?php

require_once "policies/TPPolicy.php";
require_once "policies/Policies.php";
require_once "TPRIDHash.php";
require_once "TPResource.php";
require_once "TPOffer.php";
require_once "TinyPassException.php";
require_once "TPVersion.php";
require_once "TPPriceOption.php";
require_once "ui/TPRequest.php";
require_once "ui/TPWebWidget.php";
require_once "ui/TPRequestGenerator.php";
require_once "TPClientMsgBuilder.php";
require_once "builder/TPClientBuilder.php";
require_once "builder/TPClientParser.php";
require_once "builder/TPSecureEncoder.php";
require_once "builder/TPOpenEncoder.php";
require_once "builder/TPJsonMsgBuilder.php";
require_once "builder/TPSecurityUtils.php";
require_once "token/TPTokenWrapper.php";
require_once "token/TPMeterDetails.php";
require_once "token/TPAccessToken.php";
require_once "token/TPAccessTokenList.php";
require_once "token/TPToken.php";


class TinyPass {

	public static $API_PREPARE = "/jsapi/prepare.js";
	public static $API_DATA = "/jsapi/data";
	public static $API_AUTH = "/jsapi/auth.js";

	protected $accessTokenList;
	protected $localAccessTokenList;
	protected $localBindList;

	protected $aid;
	protected $privateKey;
	protected $apiEndpoint;
	protected $msgbuilder;

	private $clientIP;
	private $lastState;

	private $generator;

	static $LOCAL_COOKIE_SUFFIX = "_TR";
	static $COOKIE_SUFFIX = "_TOKEN";
	static $COOKIE_PREFIX = "__TP_";

	public function __construct($apiEndpoint, $aid, $privateKey) {
		$this->apiEndpoint = $apiEndpoint;
		$this->aid = $aid;
		$this->privateKey = $privateKey;
		$this->localBindList = new TPAccessTokenList();

		$this->msgbuilder = new TPClientMsgBuilder($privateKey);
		$this->setClientCookies($_COOKIE);

		if($this->localAccessTokenList == null)
			$this->localAccessTokenList = new TPAccessTokenList();

		if($this->accessTokenList == null)
			$this->accessTokenList = new TPAccessTokenList();
	}

	public function setClientCookies(array $cookies) {
		if(count($cookies) == 0)
			return;

		try {
			$this->accessTokenList = $this->msgbuilder->parseAccessTokenList($this->aid, $cookies);
			$this->accessTokenList->setAID($this->aid);
		} catch (Exception $e) {
		}
		try {
			$this->localAccessTokenList = $this->msgbuilder->parseLocalTokenList($this->aid, $cookies);
			$this->localAccessTokenList->setAID($this->aid);
		} catch (Exception $e) {
			$this->localAccessTokenList = new TPAccessTokenList();
			$this->localAccessTokenList->setAID($this->aid);
		}
		$this->_cleanLocalTokens();

	}


	private function isAccessGrantedForRID($rid) {
		$this->lastState = TPAccessState::ACCESS_GRANTED;

		if ($this->accessTokenList == null) {
			$this->lastState = TPAccessState::NO_TOKENS_FOUND;
			return false;
		}

		if ($this->accessTokenList->hasIPs() && $this->isClientIPValid() && $this->accessTokenList->containsIP($this->clientIP) == false) {
			$this->lastState = TPAccessState::CLIENT_IP_DOES_NOT_MATCH_TOKEN;
			return false;
		}

		if ($this->accessTokenList->isAccessGranted($rid)) {
			return true;
		} else {
			$this->lastState = $this->accessTokenList->getLastError();
			return false;
		}

	}

	public function isAccessGranted($obj) {
		if(is_string($obj)) {
			return $this->isAccessGrantedForRID($obj);
		}

		if($obj instanceof TPResource) {
			return $this->isAccessGrantedForRID($obj->getRID());
		}

		if($obj instanceof TPOffer) {
			$granted = $this->isAccessGrantedForRID($obj->getResource()->getRID());

			if ($granted) {
				return true;
			} else {
				if ($obj->getPricing()->hasActiveOptions() == false) {
					$this->lastState = TPAccessState::NO_ACTIVE_PRICES;
					return true;
				}
			}
			return false;

		}

	}


	public function getMeterDetails($offer) {
		$resource = $offer->getResource();
		$meterDetails = null;

		/*
            Check for token in both places.  Because MeteredB from from the server we must check both places.
		*/
		if ($this->accessTokenList != null
						&& $this->accessTokenList->getToken($resource->getRID()) != null
						&& TPMeterDetails::is($this->accessTokenList->getToken($resource->getRID()))) {
			$token = $this->accessTokenList->getToken($resource->getRID());
			$meterDetails = TPMeterDetails::createWithToken($token);

		} else if ($this->localAccessTokenList == null || $this->localAccessTokenList->getToken($resource->getRID()) == null) {

			$meterDetails = TPMeterDetails::createWithValues($resource->getRIDHash(), $offer->getMeteredPolicy()->toMap());
			$this->localBindList->add($resource->getRIDHash(), $meterDetails->getToken());

		} else {

			$token = $this->localAccessTokenList->getToken($resource->getRID());
			$meterDetails = TPMeterDetails::createWithToken($token);
			$this->localBindList->add($resource->getRIDHash(), $token);
		}
		try {
			$meterDetails->touch();
		} catch (Exception $tokenUnparseable) {
			//tokenUnparseable.printStackTrace();
		}

		return $meterDetails;
	}

	public function __generateLocalCookie() {


		foreach($this->localBindList->getTokens() as $key => $token) {
			$this->localAccessTokenList->add($key, $token);
		}

		if ($this->localAccessTokenList->size() > 0)
			return self::getAppPrefix($this->getAID()) . TinyPass::$LOCAL_COOKIE_SUFFIX  . "=" . urlencode($this->getMsgbuilder()->buildAccessTokenList($this->getLocalAccessTokenList()));

		return "";
	}

	public function __hasLocalTokenChanges() {
		return $this->localBindList->isEmpty() == false;
	}

	private function _cleanLocalTokens() {
		$tokens = $this->localAccessTokenList->getTokens();

		foreach($tokens as $rid => $token) {

			if(TPMeterDetails::is($token)) {
				$meterDetails = new TPMeterDetails($token);
				if ($meterDetails->isDead())
					$this->localAccessTokenList->remove($rid);

			} else {
				$accessToken = new TPAccessToken($token);
				if($accessToken->isExpired())
					$this->localAccessTokenList->remove($rid);
			}

		}
	}

	public function isClientIPValid() {
		return $this->clientIP && preg_match("/\d{1,3}\.\d{1,3}\.\d{1,3}.\d{1,3}/", $this->clientIP);
	}

	public function getAID() {
		return $this->aid;
	}

	protected function getPrivateKey() {
		return $this->privateKey;
	}

	public static function getAppPrefix($aid) {
		return "__TP_" . $aid;
	}

	public function getApiEndPoint() {
		return $this->apiEndpoint;
	}

	public function getMsgBuilder() {
		return $this->msgbuilder;
	}

	public function getAccessTokenList() {
		return $this->accessTokenList;
	}

	public function getRequestGenerator() {
		if(!isset($this->generator))
			$this->generator = new TPRequestGenerator($this);
		return $this->generator;
	}

	public function setClientIP($s) {
		$this->clientIP = $s;
	}

	public function getLocalAccessTokenList() {
		return $this->localAccessTokenList;
	}

	public function __getLocalBindingList() {
		return $this->localBindList;
	}

	public function getClientIP() {
		return $this->clientIP;
	}

	public function getAccessError() {
		return $this->lastState;
	}

}


class TPAccessState {

	const ACCESS_GRANTED = 100;
	const CLIENT_IP_DOES_NOT_MATCH_TOKEN = 200;
	const RID_NOT_FOUND = 201;
	const NO_TOKENS_FOUND = 202;
	const METERED_TOKEN_ALWAYS_DENIED = 203;
	const EXPIRED = 204;
	const NO_ACTIVE_PRICES = 205;

}
?>
