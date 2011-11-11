<?php

class TPClientBuilder {

	const TYPE_JSON = 'j';

	const ENCODING_AES = 'a';
	const ENCODING_OPEN = 'o';

	private $builder;
	private $encoder;

	private $privateKey;

	private $mask = "";

	function __construct($privateKey, $settings = null) {
		$this->privateKey = $privateKey;
		$this->mask.=("{");
		switch ($settings!=null && strlen($settings)>1 ? $settings{1} : self::TYPE_JSON) {
			case self::TYPE_JSON:
			default:
				$this->builder = new TPJsonMsgBuilder();
				$this->mask.=(self::TYPE_JSON);
		}
		switch ($settings!=null && strlen($settings)>2 ? $settings{2} : self::ENCODING_AES) {
			case self::ENCODING_OPEN:
				$this->encoder = new TPOpenEncoder();
				$this->mask.=(self::ENCODING_OPEN);
				break;
			case self::ENCODING_AES:
			default:
				$this->encoder = new TPSecureEncoder($this->privateKey);
				$this->mask.=(self::ENCODING_AES);
		}
		$this->mask.=("x");
		$this->mask.=("}");
	}

	public function buildAccessTokenList(TPAccessTokenList $accesstokenlist) {
		return $this->mask . $this->encoder->encode($this->builder->buildAccessTokenList($accesstokenlist));
	}

	public function buildTicketRequest($tickets) {

		if($tickets instanceof TPTicket) {
			$tickets = array($tickets);
		}

		return $this->mask . $this->encoder->encode($this->builder->buildTicketRequest($tickets));
	}

}

?>