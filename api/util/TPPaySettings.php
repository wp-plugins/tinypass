<?php

/**
 * Options Helper Class used generically across PHP plugins
 */
class TPPaySettings {

	const RESOURCE_NAME = 'resource_name';
	const RESOURCE_ID = 'resource_id';
	const PO_PRICE = 'po_p';
	const PO_PERIOD = 'po_ap';
	const PO_TYPE = 'po_type';
	const PO_CAPTION = 'po_cap';
	const PO_START = 'po_st';
	const PO_END = 'po_et';
	const METERED = 'metered';
	const METER_LOCKOUT_PERIOD = 'm_lp';
	const METER_LOCKOUT_PERIOD_TYPE = 'm_lp_type';
	const METER_MAX_ACCESS_ATTEMPTS = 'm_maa';
	const METER_TRIAL_PERIOD = 'm_tp';
	const METER_TRIAL_PERIOD_TYPE = 'm_tp_type';

	public function __construct($data) {

		$this->data = new NiceArray($data);

		if ($this->_isset(self::RESOURCE_NAME))
			$this->resource_name = $data[self::RESOURCE_NAME];

		if ($this->_isset(self::RESOURCE_ID))
			$this->resource_id = $data[self::RESOURCE_ID];

		$count = 0;
		for ($i = 1; $i <= 3; $i++) {
			if ($this->_isset('po_en' . $i))
				$count++;
		}

		$this->num_prices = $count;
	}

	public function isEnabled() {
		return $this->_isset('en');
	}

	private function _isset($field) {
		return isset($this->data[$field]) && ($this->data[$field] || $this->data[$field] == 'on');
	}

	public function getResourceName() {
		return $this->resource_name;
	}

	public function setResourceName($s) {
		$this->resource_name = $s;
	}

	public function getResourceId() {
		return $this->resource_id;
	}

	public function setResourceId($s) {
		$this->resource_id = $s;
	}

	public function getNumPrices() {
		return $this->num_prices;
	}

	public function getPrice($i) {
		return $this->data[self::PO_PRICE . "$i"];
	}

	public function getAccess($i) {
		if ($this->data[self::PO_PERIOD . $i] == '' || $this->data[self::PO_TYPE . $i] == '')
			return '';
		return $this->data[self::PO_PERIOD . $i] . " " . $this->data[self::PO_TYPE . $i];
	}

	public function getCaption($i) {
		return $this->data[self::PO_CAPTION . $i];
	}

	public function getStartDateSec($i) {
		return strtotime($this->data[self::PO_START . $i]);
	}

	public function getEndDateSec($i) {
		return strtotime($this->data[self::PO_END . $i]);
	}

	public function isMetered() {
		if ($this->_isset(self::METERED)) {
			return in_array($this->data[self::METERED], array('count', 'time'));
		}
		return false;
	}

	public function isTimeMetered() {
		return $this->isMetered() && $this->data[self::METERED] == 'time';
	}

	public function isCountMetered() {
		return $this->isMetered() && $this->data[self::METERED] == 'count';
	}

	public function getLockoutPeriod() {
		return $this->data[self::METER_LOCKOUT_PERIOD] . " " . $this->data[self::METER_LOCKOUT_PERIOD_TYPE];
	}

	public function getMaxAccessAttempts() {
		return $this->data[self::METER_MAX_ACCESS_ATTEMPTS];
	}

	public function getTrialPeriod() {
		return $this->data[self::METER_TRIAL_PERIOD] . " " . $this->data[self::METER_TRIAL_PERIOD_TYPE];
	}

}

?>
