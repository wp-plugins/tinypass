<?php

class TPAccessTokenList {

	public static $MAX = 20;
	private $aid;
	private $uid;
	private $tokens = array();
	private $rawToken = "";
	private $buildTime = 0;
	private $ips = array();
	private $lastError = 0;


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

	/*
	public function addResourceByHash($ridHash, $expiration) {
		$this->add($ridHash, new TPAccessToken($ridHash, $expiration));
	}

	public function addResource($rid, $expiration) {
		$ridHash = TPRIDHash::hashCode($rid);
		$this->add($ridHash, new TPAccessToken($ridHash, $expiration));
	}
	*/
	public function remove($key) {
		unset($this->tokens[$key]);
	}

	public function add($ridHash, $token) {
		if (count($this->tokens) >= TPAccessTokenList::$MAX) {
			array_pop($this->tokens);
		}
		if($ridHash instanceof TPRIDHash)
			$this->tokens[$ridHash->getHash()] = $token;
		else {
			$hash = TPRIDHash::hashCode($ridHash);
			$this->tokens[$hash] = $token;
		}

	}

	public function isAccessGranted($rid) {
		$this->lastError = TPAccessState::ACCESS_GRANTED;
		$hash = TPRIDHash::hashCode($rid);

		if (array_key_exists($hash, $this->tokens)) {
			$token = $this->tokens[$hash];
			if (TPMeterDetails::is($token) == false) {
				$accessToken = new TPAccessToken($token);
				$expired = $accessToken->isExpired();
				if ($expired) {
					$this->lastError = TPAccessState::EXPIRED;
					return false;
				}
				return true;
			} else {
				$this->lastError = TPAccessState::METERED_TOKEN_ALWAYS_DENIED;
				return false;
			}
		}
		$this->lastError = TPAccessState::RID_NOT_FOUND;
		return false;
	}

	public function setRawToken($rawToken) {
		$this->rawToken = $rawToken;
	}

	public function getRawToken() {
		return $this->rawToken;
	}

	/**
	 *
	 * @param <String> $rid String RID
	 */
	public function getToken($rid) {
		$hash = TPRIDHash::hashCode($rid);
		if (array_key_exists($hash, $this->tokens)) {
			return $this->tokens[$hash];
		}
		return null;
	}

	public function setBuildTime($time) {
		$this->buildTime = $time;
	}

	public function isEmpty() {
		return $this->tokens == null || count($this->tokens) == 0;
	}

	public function size() {
		return count($this->tokens);
	}

	public function containsIP($currentIP) {
		return in_array($currentIP, $this->ips);
	}

	public function addIP($ip) {
		$this->ips[] = $ip;
		return $this;
	}

	public function getIPs() {
		return $this->ips;
	}

	public function hasIPs() {
		return $this->ips != null && count($this->ips) > 0;
	}

	public function setIPs($list) {
		$this->ips = $list;
	}

	public function getLastError() {
		return $this->lastError;
	}

}
?>