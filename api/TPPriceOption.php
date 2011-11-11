<?php


require_once 'TinyPassException.php';

class TPPriceOption {

	protected $price;
	protected $accessPeriod;
	protected $initAccessPeriod;
	protected $startDateInSecs;
	protected $endDateInSecs;
	protected $caption;

	protected $splitPay = array();

	private static $EXPIRE_PARSER = '/(\d+)\s*(\w+)/';

	public function __construct($price = null, $acessPeriod = null, $startDateInSecs = null, $endDateInSecs = null) {
		$this->setPrice($price);
		$this->accessPeriod = $acessPeriod;
		if($startDateInSecs)
			$this->startDateInSecs = TPToken::convertToEpochSeconds($startDateInSecs);
		if($endDateInSecs)
			$this->endDateInSecs = TPToken::convertToEpochSeconds($endDateInSecs);
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
		if (preg_match("/^-?\d+$/", $period)) {
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

					if (strlen($str) > 1 && $str[1] == 'i')
						return $num * 60 * 1000;
					else if (strlen($str) > 1 && $str[1] == 's')
						return $num * 60;
					else if (strlen($str) == 1 || $str[1] == 'o')
						return $num * 30 * 24 * 60 * 60 * 1000;

				case 'h':
					return $num * 60 * 60 * 1000;
				case 'd':
					return $num * 24 * 60 * 60 * 1000;
			 case 'w':
					return $num * 7 * 24 * 60 * 60 * 1000;
			}
		}

		throw new TinyPassException("Cannot parse the specified period: " . $period);
	}

	public function setAccessPeriod($expires) {
		$this->accessPeriod = $expires;
		return $this;
	}

	public function getStartDateInSecs() {
		return $this->startDateInSecs;
	}

	public function setStartDateInSecs($startDateInSecs) {
		$this->startDateInSecs = $startDateInSecs;
		return $this;
	}

	public function getEndDateInSecs() {
		return $this->endDateInSecs;
	}

	public function setEndDateInSecs($endDate) {
		$this->endDateInSecs = $endDate;
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

	public function isActive($timestampSecs) {
		$timestampSecs = TPToken::convertToEpochSeconds($timestampSecs);
		if ($this->getStartDateInSecs() != null && $this->getStartDateInSecs() > $timestampSecs) return false;
		if ($this->getEndDateInSecs() != null && $this->getEndDateInSecs() < $timestampSecs) return false;
		return true;
	}


	public function __toString() {
		$sb = "";
		sb.("Price:").($this->getPrice());
		sb.("\tPeriod:").($this->getAccessPeriod());

		if ($this->getStartDateInSecs() != null) {
			sb.("\tStart:").($this->getStartDateInSecs()).(":").( date('D, d M Y H:i:s' , $this->getStartDateInSecs()));
		}

		if ($this->getEndDateInSecs() != null) {
			sb.("\tEnd:").($this->getEndDateInSecs()).(":").(date('D, d M Y H:i:s', $this->getEndDateInSecs()));
		}

		if ($this->getCaption() != null) {
			sb.("\tCaption:").($this->getCaption());
		}

		if ($this->splitPay != null) {
			foreach ($this->splitPay as $key => $value)
				sb.("\tSplit:").($key).(":").($value);
		}
		return $sb;
	}

}
?>