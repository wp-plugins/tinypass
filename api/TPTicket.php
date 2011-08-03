<?php

require_once 'TPPriceOption.php';
require_once 'builder/TPBuilderFactory.php';
require_once 'builder/TPParserFactory.php';

class TPTicket {


	protected $resource;
	protected $priceOptions = array();
	protected $tags = array();
	protected $timestamp;

	protected $upSellTicket;
	protected $crossSellTicket;

	function __construct(TPResource $resource, TPPriceOption $options = null) {
		if($resource == null) throw new TinyPassException("Resource is not specifed");
		if($options != null && count($options) > 0) $this->addPriceOption($options);
		$this->resource = $resource;
		$this->timestamp = time();
	}

	public function getResource() {
		return $this->resource;
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

	public function addExternalReferenceID($refID) {
		$this->addTag("ref:" . $refID);
		return $this;
	}

	public function getTimestamp() {
		return $this->timestamp;
	}

	public function addPriceOption($po) {
		if(is_array($po)) {
			array_merge($this->priceOptions, $po);
		} else {
			array_push($this->priceOptions, $po);
		}
		return $this;
	}


	public function getPriceOptions() {
		return $this->priceOptions;
	}

	public function getPriceOption($position) {
		return $this->priceOptions[$position];
	}

	public function getMinPrice() {
		$price = null;

		foreach($this->getPriceOptions() as $po) {

			$validPO = null;

			if ($po->getStartDate() == null && $po->getEndDate() == null) {

				$validPO = $po;

			} else if ($po->getStartDate() != null && $po->getEndDate() != null) {

				if ($po->getStartDate() <= $this->getTimestamp() && $po->getEndDate() >= $this->getTimestamp())
					$validPO = $po;

			} else if ($po->getStartDate() != null && $po->getStartDate() <= $this->getTimestamp() && $po->getEndDate() == null) {

				$validPO = $po;

			} else if ($po->getStartDate() == null && $po->getEndDate() != null && $po->getEndDate() >= $this->getTimestamp()) {

				$validPO = $po;

			}

			if($validPO && ($price == null || $validPO->getPrice() < $price ))
				$price = $validPO->getPrice();

		}
		return $price;
	}

	/**
	 * A completentary resource, which will be offered along with this resource in the ticket popup
	 * (the crosssell case)
	 *
	 * @param resource
	 */
	public function setCrossSellTicket(TPTicket $ticket) {
		if($ticket->getResource()->getRID() == ($this->resource->getRID())) throw new TinyPassException("Cross-Sell resource and the main resource cannot have the same RID");
		$this->upSellTicket = null;
		$this->crossSellTicket = $ticket;
		return $this;
	}

	/**
	 * A completentary resource, which will be offered along with this resource in the ticket popup
	 * (the upsale case)
	 *
	 * @param resource
	 */
	public function setUpSellTicket(TPTicket $ticket) {
		if($ticket->getResource()->getRID() == ($this->resource->getRID())) throw new TinyPassException("Up-Sell resource and the main resource cannot have the same RID");
		$this->crossSellTicket = null;
		$this->upSellTicket = $ticket;
		return $this;
	}

	public function getUpSellTicket() {
		return $this->upSellTicket;
	}

	public function getCrossSellTicket() {
		return $this->crossSellTicket;
	}

	public function getBundledTicket() {
		if($this->upSellTicket!=null) return $this->upSellTicket;
		if($this->crossSellTicket!=null) return $this->crossSellTicket;
		return null;
	}

}


?>