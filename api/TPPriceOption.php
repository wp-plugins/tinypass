<?php


require_once 'TinyPassException.php';

class TPPriceOption {

	protected $price;
	protected $accessPeriod;
	protected $initAccessPeriod;
	protected $startDate;
	protected $endDate;
	protected $caption;
	protected $splitPay = array();
	private static $SUPPORTED_PRICES = '$0.02-$0.05, $0.05-$0.95 with $0.05 increments and $0.99';
	private static $EXPIRE_PARSER = '/(\d+)\s*(\w+)/';

	public function __construct($price = null, $acessPeriod = null, $startDate = null, $endDate = null) {
		$this->setPrice($price);
		$this->accessPeriod = $acessPeriod;
		$this->startDate = $startDate;
		$this->endDate = $endDate;
	}

	public function getPrice() {
		return $this->price;
	}

	public function setPrice($price) {
		$this->price = $price;
		return $this;
	}

	public function getAccessPeriod() {
		return $this->accessPeriod;
	}

	public function getAccessPeriodInMsecs() {
		if ($this->initAccessPeriod != null) return $this->initAccessPeriod;

		if ($this->accessPeriod == null) return null;

		return $this->initAccessPeriod = $this->parseLoosePeriod($this->accessPeriod);
	}

	public function getAccessPeriodInSecs() {
		return $this->getAccessPeriodInMsecs() / 1000;
	}

	public static function parseLoosePeriod($period) {
		if (preg_match("/^\d+$/", $period)) {
			return (int)$period;
		}
		$matches = array();
		if (preg_match(TPPriceOption::$EXPIRE_PARSER, $period, $matches)) {
			$num = $matches[1];
			$str = $matches[2];
			switch ($str[0]) {
				case 's':
					return $num * 1000;
				case 'm':
					if($str[1] == 'i')
						return $num * 60 * 1000;
					else if($str[1] == 'o')
						return $num * 30 * 7 * 24 * 60 * 60 * 1000;
				case 'h':
					return $num * 60 * 60 * 1000;
				case 'd':
					return $num * 24 * 60 * 60 * 1000;
			}
		}

		throw new TinyPassException("Cannot parse the specified period: " . $period);
	}

	public function setAccessPeriod($expires) {
		$this->accessPeriod = $expires;
		return $this;
	}

	public function getStartDate() {
		return $this->startDate;
	}

	public function setStartDate($startDate) {
		$this->startDate = $startDate;
		return $this;
	}

	public function getEndDate() {
		return $this->endDate;
	}

	public function setEndDate($endDate) {
		$this->endDate = $endDate;
		return $this;
	}

	public function addSplitPay($email, $amount) {
		if(preg_match('/%$/', $amount)) {
			$amount = (double)substr($amount, 0, strlen($amount)-1);
			$amount = $amount / 100.0;
		}
		$this->splitPay[$email] = $amount;
		return $this;
	}

	public function getSplitPays() {
		return $this->splitPay;
	}

	public function getCaption() {
		return $this->caption;
	}

	public function setCaption($caption) {
		if($caption!=null && strlen($caption) > 23)
			$caption = substr($caption, 0, 23);
		$this->caption = $caption;
		return $this;
	}

	public function validate() {
		/*
		if ($this->price == null) throw new TinyPassException("Price is not defined");
		if ($this->price.compareTo(NumberUtils.POINT_02) < 0)
			throw new TinyPassException("Price should be a value between $0.2 and $0.99");
		if ($this->price.compareTo(NumberUtils.POINT_99) > 0) {
			throw new TinyPassException("Price should be a value between $0.2 and $0.99");
		}
		if ($this->price > 2) {
			throw new TinyPassException("Supported price values are " + TPPriceOption::$SUPPORTED_PRICES);
		}
		if ((this.price.compareTo(NumberUtils.POINT_05) > 0) && (this.price.compareTo(NumberUtils.POINT_99) > 0) && (this.price.divide(NumberUtils.POINT_05).scale() > 0)) {
			throw new TinyPassException("Supported price values are " + TPPriceOption::$SUPPORTED_PRICES);
		}

		if ((getAccessPeriodInMsecs() == null) && (this.accessPeriod != null)) {
			throw new TinyPassException("Expiration value couldn't be parsed: " + this.accessPeriod);
		}

		$total = 0;
		foreach($this->splitPay as $email => $amount) {
			if ($amount == null)
				throw new TinyPassException("Split price is not defined for " + $email);
			if ($amount <= 0)
				throw new TinyPassException("Split price is a negative or zero value " + $email);
			if ($amount >= 0)
				throw new TinyPassException("Split price should be a value between 0 and 0.99 or 0% and 99% " + $email);
			$total = $total + $amount;
		}

		if ($total > 1)
			throw new TinyPassException("The total amount of split pays exceeds 100%");
		 */
	}
}
?>