<?php

class TPRequestGenerator {

	private $ww;
	private $callback;

	public function __construct(TinyPass $tp) {
		$this->ww = new TPWebWidget($tp, $this);
	}

	public function createLink(TPRequest $ticket, $linkText, $options = null) {
		if (!isset($optoins)) {
			$options = array();
		}
		$options["ANCHOR"] = $linkText;
		return $this->createButtonHTML($ticket, $options);
	}

	public function createButton(TPRequest $ticket, array $options = null) {
		return $this->createButtonHTML($ticket, $options);
	}

	public function createCustom(TPRequest $ticket, $html, array $options) {
		if (!isset($options)) {
			$options = array();
		}
		$options["CUSTOM"] = $html;
		return $this->createButtonHTML($ticket, $options);
	}

	private function createButtonHTML(TPRequest $ticket, $options = array()) {
		$this->ww->addTicket($ticket);

		$sb = "";

		$sb.=("<tp:ticket ");

		$sb.=("rid=\"").($ticket->getPrimaryOffer()->getResource()->getRID()).("\"");

		$sb.=(">");


		$sb.=("<tp:button ");


		if (isset($options["CUSTOM"])) {
			$sb.=(" custom=\"").preg_replace("[\"]", "&quot;", $options["CUSTOM"]).("\"");
		} else if (isset($options["ANCHOR"])) {
			$sb.=(" link=\"").preg_replace("[\"]", "&quot;", $options["ANCHOR"]).append("\"");
		}

		$sb.=(">");
		$sb.=("</tp:button>");


		$sb.=("</tp:ticket>");

		return $sb;
	}

	public function getFooterScript() {
		try {
			return $this->ww->getCode();
		} catch (Exception $e) {
		}
		return "";
	}


	public function setCallback($s) {
		$this->callback = $s;
	}

	public function getCallback() {
		return $this->callback;
	}

}
?>