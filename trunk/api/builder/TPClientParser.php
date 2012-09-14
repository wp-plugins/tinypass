<?php

class TPClientParser {

	private $privateKey;
	private $builder;
	private $encoder;

	function __construct($privateKey) {
		$this->privateKey = $privateKey;
	}

	private function checkSettings($str) {

		switch ($str!=null && strlen($str)>1 ? $str{1} : TPClientBuilder::TYPE_JSON) {

			case TPClientBuilder::TYPE_JSON:
			default:
				$this->builder = new TPJsonMsgBuilder();

		}
		switch ($str!=null && strlen($str)>2 ? $str{2} : TPClientBuilder::ENCODING_AES) {

			case TPClientBuilder::ENCODING_OPEN:
				$this->encoder = new TPOpenEncoder();
				break;

			case TPClientBuilder::ENCODING_AES:
			default:
				$this->encoder = new TPSecureEncoder($this->privateKey);
		}
		return preg_replace('/^{...}/','', $str);
	}

	public function parseAccessTokenList($message) {
		$s = $this->checkSettings($message);
		return $this->builder->parseAccessTokenList($this->encoder->decode($s));
	}

}
?>