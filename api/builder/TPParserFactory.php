<?php


require_once 'TPBuilderFactory.php';
require_once 'TPJsonMsgParser.php';
require_once 'TPOpenDecoder.php';
require_once 'TPSecureDecoder.php';
require_once dirname(__FILE__). '/../TPTicket.php';
require_once dirname(__FILE__). '/../TPResource.php';

class TPParserFactory {

	private $privateKey;
	private $message;
	private $parser;
	private $decoder;

	function __construct($privateKey, $message) {
		$this->privateKey = $privateKey;

		switch ($message!=null && strlen($message)>1 ? $message{1} : TPBuilderFactory::TYPE_JSON) {
			case TPBuilderFactory::TYPE_JSON:
			default:
				$this->parser = new TPJsonMsgParser();
		}
		switch ($message!=null && strlen($message)>2 ? $message{2} : TPBuilderFactory::ENCODING_AES) {
			case TPBuilderFactory::ENCODING_OPEN:
				$this->decoder = new TPOpenDecoder();
				break;
			case TPBuilderFactory::ENCODING_AES:
			default:
				$this->decoder = new TPSecureDecoder($this->privateKey);
		}
		$this->message = preg_replace('/^{...}/','', $message);
	}

	public function parseAccessTokenList() {
		return $this->parser->parseAccessTokenList($this->decoder->decode($this->message));
	}

	public function parseTicket() {
		return $this->parser->parseTicket($this->decoder->decode($this->message));
	}

	public function parseTickets() {
		return $this->parser->parseTickets($this->decoder->decode($this->message));
	}
/*
	public static function parseAccessTokenList($key, $message) {
		$factory = new ParserFactory($key, $message);
		return $factory->parseAccessTokenList();
	}

	public static function parseTicket($key, $message) {
		$factory = new ParserFactory($key, $message);
		return $factory->parseTicket();
	}

	public static function parseTickets($key, $message) {
		$factory = new ParserFactory($key, $message);
		return $factory->parseTickets();
	}
 */

}
?>