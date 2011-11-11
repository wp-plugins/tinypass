<?php

class TPRequest {


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

}


?>
