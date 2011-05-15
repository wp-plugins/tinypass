<?php

require_once 'TPRIDHash.php';
require_once 'TPAccessToken.php';

class TPAccessTokenList {

	public static $TRIAL_LOCKOUT_ENDTIME = "tr_let";
	public static $TRIAL_ENDTIME = "tr_et";
	public static $TRIAL_ACCESS_MAX_ATTEMPTS = "tr_ma";
	public static $TRIAL_ACCESS_ATTEMPTS = "tr_aa";
	public static $TRIAL_ACCESS_ATTEMPTS_ORIG = "tr_aao";

	public static $NEVER = 0;
	public static $MAX = 20;
	private $aid;
	private $uid;
	private $tokens = array();
	private $rawToken = "";
	private $buildTime = 0;

	function __construct($aid = null, $uid = null) {
		$this->aid = $aid;
		$this->uid = $uid;
	}

	public function getAID() {
		return $this->aid;
	}

	public function getUID() {
		return $this->uid;
	}

	public function getTokens() {
		return $this->tokens;
	}

	function setAID($aid) {
		$this->aid= $aid;
	}

	function setUID($uid) {
		$this->uid = $uid;
	}

	public function contains($rid) {
		$ridHash = TPRIDHash::hashCode($rid);
		return array_key_exists($ridHash, $this->tokens);
	}

	public function getToken($rid) {
		$ridHash = TPRIDHash::hashCode($rid);
		if(array_key_exists($ridHash, $this->tokens)){
			return $this->tokens[$ridHash];
		}
		return null;
	}

	public function addResourceByHash($ridHash, $expiration) {
		$this->add($ridHash, new TPAccessToken($ridHash, $expiration));
	}

	public function addResource($rid, $expiration) {
		$ridHash = TPRIDHash::hashCode($rid);
		$this->add($ridHash, new TPAccessToken($ridHash, $expiration));
	}

	public function add($ridHash, $token) {
		if (count($this->tokens) >= TPAccessTokenList::$MAX) {
			array_pop($this->tokens);
		}
		if($ridHash instanceof TPRIDHash)
			$this->tokens[$ridHash->getHash()] = $token;
		else
			$this->tokens[$ridHash] = $token;

	}

	public function isAccessGranted($rid) {
		$hash = TPRIDHash::hashCode($rid);
		if (array_key_exists($hash, $this->tokens)) {
			$token = $this->tokens[$hash];
			$expires = $token->getExpiration();
			return ($expires == 0 || $expires >= time());
		}
		return false;
	}

	public function cleanExpired() {
		foreach($this->tokens as $rid => $token) {
			$expires = $token->getExpiration();
			if ($expires != null && ($expires != 0 && time() > $expires))
				unset($this->tokens[$rid]);
		}
	}


	public function setRawToken($rawToken) {
		$this->rawToken = $rawToken;
	}

	public function getRawToken() {
		return $this->rawToken;
	}

	public function setBuildTime($time) {
		$this->buildTime = $time;
	}

}
?>