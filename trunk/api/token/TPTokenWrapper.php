<?php

class TPTokenWrapper {

	protected $token;

	public function __construct() {
		$this->token = new TPToken();
	}

	public function getToken() {
		return $this->token;
	}

	public function setToken($token) {
		return $this->token = $token;
	}

	public function addField($key, $value) {
		$this->token->addField($key, $value);
	}

}
?>