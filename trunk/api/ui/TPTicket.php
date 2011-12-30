<?php

class TPTicket {


	private $primaryOffer;
	private $secondaryOffer;
	private $options;
	private $clientIP;
	private $version;


	private $tags = array();
	private $timestampInSeconds;



	function __construct(TPOffer $offer, array $options = null) {
		$this->primaryOffer = $offer;

		if($options == null)
			$options = array();

		$this->options = $options;

		$this->version = TPVersion::getVersion() + "_p";

		$this->timestampInSeconds = time();

	}

	public function getPrimaryOffer() {
		return $this->primaryOffer;
	}

	public function getSecondaryOffer() {
		return $this->secondaryOffer;
	}

	public function setSecondaryOffer(TPOffer $offer) {
		$this->secondaryOffer = $offer;
		return $this;
	}

	public function addTag($tag) {
		if ($tag == null || count($tag) == 0) return $this;
		$tag = trim($tag);
		array_push($this->tags, $tag);
		return $this;
	}

	public function addTags(array $tags) {
		foreach($tags as $tag) {
			$this->addTag($tag);
		}
		return $this;
	}

	public function getTags() {
		return $this->tags;
	}

	public function getOptions() {
		return $this->options;
	}

	public function getTimestampSecs() {
		return $this->timestampInSeconds;
	}

	public function setClientIP($currentIP) {
		$this->clientIP = $currentIP;
	}

	public function getClientIP() {
		return $this->clientIP;
	}

	public function setOptions(array $options) {
		$this->options = $options;
	}

	public function getVersion() {
		return $this->version;
	}

	public function setVersion($version) {
		$this->version = $version;
	}

	public function createLink($linkText, $options = null) {
		if (!isset($optoins)) {
			$options = array();
		}
		$options["ANCHOR"] = $linkText;
		return $this->createButtonHTML($options);
	}

	public function createButton(array $options = null) {
		return $this->createButtonHTML($options);
	}

	public function createCustom($html, array $options) {
		if (!isset($options)) {
			$options = array();
		}
		$options["CUSTOM"] = $html;
		return $this->createButtonHTML($options);
	}

	private function createButtonHTML($options = array()) {

		$sb = "";

		$sb.=("<tp:ticket ");

		$sb.=("rid=\"").($this->getPrimaryOffer()->getResource()->getRID()).("\"");

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


}


?>
