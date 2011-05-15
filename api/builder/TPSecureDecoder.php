<?php

class TPSecureDecoder {

	private $privateKey;

	function __construct($privateKey) {
		$this->privateKey = $privateKey;
	}

	public function decode($msg) {
		return TPSecurityUtils::decrypt($this->privateKey, $msg);
	}
}

?>
