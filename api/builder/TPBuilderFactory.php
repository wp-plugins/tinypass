<?php

require_once 'TPJsonMsgBuilder.php';
require_once 'TPOpenEncoder.php';
require_once 'TPSecureEncoder.php';
require_once dirname(__FILE__). '/../TPTicket.php';
require_once dirname(__FILE__). '/../TPResource.php';

class TPBuilderFactory {

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

	public function buildTicket(TPTicket $ticket) {
		return $this->mask . $this->encoder->encode($this->builder->buildTicket($ticket));
	}

	public function buildAccessTokenList(TPAccessTokenList $accesstokenlist) {
		return $this->mask . $this->encoder->encode($this->builder->buildAccessTokenList($accesstokenlist));
	}

//	public function buildAccessTokenList(TPTrialAccessTokenList $accesstokenlist) {
//		return $this->mask . $this->encoder->encode($this->builder->buildAccessTokenList($accesstokenlist));
//	}

	public function buildTickets($tickets) {
		return $this->mask . $this->encoder->encode($this->builder->buildTickets($tickets));
	}

}

?>