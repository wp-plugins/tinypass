<?php

require_once 'TPAccessTokenList.php';
/**
 * User: tdirrenb
 * Date: Feb 28, 2011
 * Time: 10:34:54 PM
 * Requires PHP 5.2 or later (5.2.17 or 5.3.5 highly recommended) with the following extensions:

 * SimpleXML
 * JSON
 * PCRE
 * SPL
 * cURL with HTTPS support
 */
class TPAccessToken {

	private $map = array();


	public function __construct($ridhash, $expiration) {
		$this->map["rid"] = $ridhash;
		$this->map["ex"] = $expiration != null ? $expiration : 0;
	}

	public function getValues() {
		return $this->map;
	}

	public function getRidHash() {
		return $this->map["rid"];
	}

	public function getExpiration() {
		if (!isset($this->map["ex"]))
			return 0;
		return $this->map["ex"];
	}


	public function addField($field, $value) {
		$this->map[$field] = $value;
	}

	public function getTrialEndTime() {
		if (!isset($this->map[TPAccessTokenList::$TRIAL_ENDTIME]))
			return 0;
		return $this->map[TPAccessTokenList::$TRIAL_ENDTIME];
	}

	public function setTrialEndTime($t) {
		$this->map[TPAccessTokenList::$TRIAL_ENDTIME] = $t;
	}

	public function getTrialLockoutEndTime() {
		if (!isset($this->map[TPAccessTokenList::$TRIAL_LOCKOUT_ENDTIME]))
			return 0;
		return $this->map[TPAccessTokenList::$TRIAL_LOCKOUT_ENDTIME];
	}

	public function setTrialLockoutEndTime($l) {
		$this->map[TPAccessTokenList::$TRIAL_LOCKOUT_ENDTIME] =  $l;
	}

	public function getTrialAccessAttempts() {
		if (!isset($this->map[TPAccessTokenList::$TRIAL_ACCESS_ATTEMPTS]))
			return 0;
		return $this->map[TPAccessTokenList::$TRIAL_ACCESS_ATTEMPTS];
	}

	public function setTrialAccessAttempts($l) {
		$this->map[TPAccessTokenList::$TRIAL_ACCESS_ATTEMPTS] = $l;
	}

	public function getTrialAccessAttemptsOrig() {
		if (!isset($this->map[TPAccessTokenList::$TRIAL_ACCESS_ATTEMPTS_ORIG]))
			return 0;
		return $this->map[TPAccessTokenList::$TRIAL_ACCESS_ATTEMPTS_ORIG];
	}

	public function setTrialAccessAttemptsOrig($l) {
		$this->map[TPAccessTokenList::$TRIAL_ACCESS_ATTEMPTS_ORIG] =  $l;
	}


	public function getTrialAccessMaxAttempts() {
		if (!isset($this->map[TPAccessTokenList::$TRIAL_ACCESS_MAX_ATTEMPTS]))
			return 0;
		return $this->map[TPAccessTokenList::$TRIAL_ACCESS_MAX_ATTEMPTS];
	}

	public function setTrialAccessMaxAttempts($l) {
		$this->map[TPAccessTokenList::$TRIAL_ACCESS_MAX_ATTEMPTS]  =$l;
	}

}
?>