<?php

/**
 * Requires PHP 5.2 or later (5.2.17 or 5.3.5 highly recommended) with the following extensions:

 * SimpleXML
 * JSON
 * PCRE
 * SPL
 * cURL with HTTPS support
 */
class TPAccessToken extends TPTokenWrapper {

	public function __construct() {
		parent::__construct();

		$numargs = func_num_args();

		if($numargs == 1 && func_get_arg(0) instanceof TPToken) {
			$this->token = func_get_arg(0);
			return;
		}

		if($numargs == 2) {
			$ridhash = func_get_arg(0);
			$expiration = func_get_arg(1);
			$this->token->addField(TPToken::RID,$ridhash->toString());
			$this->token->addField(TPToken::EX, $expiration != null ? TPToken::convertToEpochSeconds($expiration) : 0);
			return;
		}

		if($numargs == 3) {
			$ridhash = func_get_arg(0);
			$expiration = func_get_arg(1);
			$eex = func_get_arg(2);
			$this->token->addField(TPToken::RID,$ridhash->toString());
			$this->token->addField(TPToken::EX, $expiration != null ? TPToken::convertToEpochSeconds($expiration) : 0);
			$this->token->addField(TPToken::EARLY_EX, $eex != null ? TPToken::convertToEpochSeconds($eex) : 0);
			return;
		}


	}

	public function getExpirationInMillis() {
		return $this->getExpirationInSeconds() * 1000;
	}

	public function getExpirationInSeconds() {
		return $this->token->getFromMap(TPToken::EX, 0);
	}

	public function getPreExpirationInSeconds() {
		return $this->token->getFromMap(TPToken::EARLY_EX, 0);
	}

	/**
	 * Access checking functions
	 */

	public function isExpired() {
		$time = $this->getPreExpirationInSeconds();

		if ($time == null || $time == 0)
			$time = $this->getExpirationInSeconds();

		if ($time == null || $time == 0)
			return false;

		return $time <= time();
	}



}
?>