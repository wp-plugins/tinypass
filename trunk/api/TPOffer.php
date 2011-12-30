<?php


class TPOffer {

	private $resource;
	private $pricing;
	private $policies = array();

	public function __construct(TPResource $resource, $priceOptions) {
		$this->resource = $resource;

		if(!is_array($priceOptions))
			$priceOptions = array($priceOptions);

		$this->pricing = TPPricingPolicy::createBasic($priceOptions);
	}

	public function getResource() {
		return $this->resource;
	}

	public function getPricing() {
		return $this->pricing;
	}

	public function isMetered() {
		foreach ($this->policies as $policy) {
			if ($policy instanceof TPMeteredPolicyImpl)
				return true;
		}
		return false;
	}

	public function addPolicy($policy) {
		$this->policies[] = $policy;
		return $this;
	}

	public function getPolicies() {
		return $this->policies;
	}

	public function getMeteredPolicy() {
		foreach ($this->policies as $policy) {
			if ($policy instanceof TPMeteredPolicyImpl)
				return $policy;
		}
		return new TPPolicy();
	}

	public function addPriceOption(TPPriceOption $priceOption) {
		$this->pricing->addPriceOption($priceOption);
	}

	public function addPriceOptions() {
		foreach(func_get_args() as $arg) {
			if($arg instanceof TPPriceOption)
				$this->pricing->addPriceOption($arg);
		}
	}
}
?>