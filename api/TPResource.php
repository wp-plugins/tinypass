<?php

require_once 'TPSecurityUtils.php';
require_once 'TinyPassException.php';
require_once 'TPPriceOption.php';

class TPResource {

	protected $rid;
	protected $rname;
	protected $timestamp;
	protected $priceOptions = array();
	protected $tags = array();

	protected $bundledResource;
	protected $upsell = false;
	private $isTrial = false;

	protected $trialPeriod = 0;
	protected $trialMaxAttempts = -1;
	protected $trialLockoutPeriod = 0;

	function __construct($rid = null, $rname = null) {
		$this->rid = $rid;
		$this->rname = $rname;
		$this->timestamp = time();
	}

	public function getRID() {
		return $this->rid;
	}

	public function setRID($rid) {
		$this->rid = $rid;
		return $this;
	}

	public function getResourceName() {
		return $this->rname;
	}

	public function getRIDHash() {
		return TPSecurityUtils::hashCode($this->rid);
	}

	public function setResourceName($rname) {
		$this->rname = $rname;
		return $this;
	}

	/**
	 * A completentary resource, which will be offered along with this resource in the ticket popup
	 * (the upsale case)
	 *
	 * @param resource
	 */
	public function setUpSellResource($resource) {
		if ($resource->getBundledResource())
			throw new TinyPassException("The resource has a bundled resource, resource id: " + $resource->getRID());
		if ($this->getRID() == $resource->getRID())
			throw new TinyPassException("Bundled resource has the same id, resource id: " + $resource->getRID());
		$this->upsell = true;
		$this->bundledResource = $resource;
		return $this;
	}

	/**
	 * A completentary resource, which will be offered along with this resource in the ticket popup
	 * (the upsale case)
	 *
	 * @param resource
	 */
	public function setCrossResource($resource) {
		if ($resource->getBundledResource())
			throw new TinyPassException("The resource has a bundled resource, resource id: " + $resource->getRID());
		if ($this->getRID() == $resource->getRID())
			throw new TinyPassException("Bundled resource has the same id, resource id: " + $resource->getRID());
		$this->upsell = false;
		$this->bundledResource = $resource;
		return $this;
	}

	public function getBundledResource() {
		return $this->bundledResource;
	}

	public function isUpSell() {
		return $this->bundledResource && $this->upsell;
	}

	public function isTrial() {
		return $this->isTrial;
	}


	public function isCrossSell() {
		return $this->bundledResource && !$this->upsell;
	}

	public function getTrialPeriod() {
		return $this->trialPeriod;
	}

	public function setTrialPeriodByTime($trialPeriod, $lockoutPeriod) {
		$this->isTrial = true;
		$this->trialPeriod = $trialPeriod;
		$this->trialLockoutPeriod = $lockoutPeriod;
		return $this;
	}

	public function setTrialPeriodByAttempts($trialAccessAttempts, $lockoutPeriod) {
		$this->isTrial = true;
		$this->trialMaxAttempts = $trialAccessAttempts;
		$this->trialLockoutPeriod = $lockoutPeriod;
		return $this;
	}

	public function addExternalReferenceID($refID) {
		$this->addTag("ref:" . $refID);
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

	public function addPriceOption($po) {
//		if (count($this->priceOptions) >= 3)
//			throw new TinyPassException("Maximum of 3 price options are currently supported");
		array_push($this->priceOptions, $po);
		return $this;
	}

	public function getPriceOptions() {
		return $this->priceOptions;
	}

	public function getPriceOption($position) {
		return $this->priceOptions[$position];
	}

	public function getTimestamp() {
		return $this->timestamp;
	}

	public function getTrialMaxAttempts() {
		return $this->trialMaxAttempts;
	}

	public function getTrialLockoutPeriod() {
		return $this->trialLockoutPeriod;
	}

	public function getMinPrice() {
		$price = null;
		
		foreach($this->getPriceOptions() as $po) {
			if (
							($po->getStartDate() == null || $po->getStartDate() <= $this->getTimestamp())
							&& ($po->getEndDate() == null || $po->getEndDate() >= $this->getTimestamp())
							&& ($price == null || $po->getPrice() < $price)
				)
				$price = $po->getPrice();
		}
		return $price;
	}

	public function validate() {

		if ($this->rid == null) throw new TinyPassException("rid is not defined");
		if (count($this->priceOptions) == 0)
			throw new TinyPassException("There are no price options associated with the request");

//		if (count($this->priceOptions) > 3) throw new TinyPassException("Maximum of 3 price options are currently supported");
		$activeOptions = false;
		foreach ($this->priceOptions as $po) {
			//TODO FIXTIM
			$po->validate();
			if ($po->getStartDate() != null && $po->getStartDate() > $this->timestamp) continue;
			if ($po->getEndDate() != null && $po->getEndDate() < $this->timestamp) continue;
			$activeOptions = true;
		}
		if (!$activeOptions)
			throw new TinyPassException("There are no active price options associated with the request");
	}

}
