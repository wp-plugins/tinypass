<?php
require_once 'TPClientMsgBuilder.php';
require_once 'TPAccessTokenList.php';
require_once 'TPTrialAccessTokenList.php';
require_once 'TPWebWidget.php';
require_once 'TPVersion.php';

class TinyPass {

	public static $API_PREPARE = "/jsapi/prepare.js";

	protected $accessTokenList;
	private $ww;
	protected $trialAccessTokenList;
	protected $aid;
	protected $privateKey;
	protected $apiEndpoint;
	protected $msgbuilder;

	static $TRIAL_COOKIE_SUFFIX = "_TRIAL";
	static $COOKIE_SUFFIX = "_TOKEN";

	public function __construct($apiEndpoint, $aid, $privateKey) {
		$this->apiEndpoint = $apiEndpoint;
		$this->aid = $aid;
		$this->privateKey = $privateKey;
		$this->msgbuilder = new TPClientMsgBuilder($privateKey);
		$this->accessTokenList = new TPAccessTokenList($aid);
		$this->setClientCookies($_COOKIE);
	}

	public function setClientCookies(array $cookies) {
		try {
			$this->accessTokenList = $this->msgbuilder->parseAccessTokenList($this->aid, $cookies);
			$this->accessTokenList->setAID($this->aid);
		} catch (Exception $e) {

		}
		try {
			$this->trialAccessTokenList = $this->msgbuilder->parseTrailTokenList($this->aid, $cookies);
			$this->trialAccessTokenList->setAID($this->aid);
		} catch (Exception $e) {
			$this->trialAccessTokenList = new TPTrialAccessTokenList($this->aid);
		}
	}

	public function getAID() {
		return $this->aid;
	}

	protected function getPrivateKey() {
		return $this->privateKey;
	}

	public function getApiEndPoint() {
		return $this->apiEndpoint;
	}

	public function getBuilder() {
		return $this->msgbuilder;
	}

	public function getAccessTokenList() {
		return $this->accessTokenList;
	}

	public function getTrialAccessTokenList() {
		return $this->trialAccessTokenList;
	}

	public function initResource($rid, $name) {
		return new TPResource($rid, $name);
	}

	public function getWebWidget() {
		if($this->ww == null)
			$this->ww = new TPWebWidget($this);
		return $this->ww;
	}

	public static function getAppPrefix($aid) {
		return "__TP_" . $aid;
	}

	public function isAccessGranted($resource) {
		if(is_string($resource)) {
			return $this->isAccessGrantedForRID($resource);
		}

		$rid = $resource->getRID();
		if ($this->accessTokenList != null && $this->_isAccessGranted($resource)) {
			return true;
		} else {
			if ($resource->isTrial()) {
				$this->trialAccessTokenList->updateCount($resource);
				if ($this->trialAccessTokenList->contains($rid)) {
					if ($this->trialAccessTokenList->isTrialPeriodActive($rid)) {
						return true;
					} else if ($this->trialAccessTokenList->isLockPeriodActive($rid)) {
						return false;
					} else {
						$this->trialAccessTokenList->addResource($resource);
						return true;
					}

				} else {
					$this->trialAccessTokenList->addResource($resource);
					return true;
				}
			} else
				return false;

		}

	}

	private function isAccessGrantedForRID($aid) {
		return $this->accessTokenList->isAccessGranted($aid);
	}

	private function _isAccessGranted(TPResource $resource) {
		return $this->accessTokenList != null && (
						$resource->getMinPrice() == null ||
						$this->accessTokenList->isAccessGranted($resource->getRID())
										|| $resource->getBundledResource() != null
										&& $resource->isUpSell()
										&& $this->accessTokenList->isAccessGranted($resource->getBundledResource()->getRID())
		);
	}


}
?>
