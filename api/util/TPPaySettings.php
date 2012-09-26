<?php

/**
 * Options Helper Class used generically across PHP plugins
 */
class TPPaySettings {

	const RESOURCE_NAME = 'resource_name';
	const RESOURCE_ID = 'resource_id';
	const PO_PRICE = 'po_p';
	const PO_PERIOD = 'po_ap';
	const PO_PERIOD_TYPE = 'po_ap_type';
	const PO_CAPTION = 'po_cap';
	const PO_START = 'po_st';
	const PO_END = 'po_et';
	const PO_RECUR = 'po_recur';
	const METERED = 'metered';
	const HIDE_TEASER = 'ht';
	const METER_LOCKOUT_PERIOD = 'm_lp';
	const METER_LOCKOUT_PERIOD_TYPE = 'm_lp_type';
	const METER_MAX_ACCESS_ATTEMPTS = 'm_maa';
	const METER_TRIAL_PERIOD = 'm_tp';
	const METER_TRIAL_PERIOD_TYPE = 'm_tp_type';
	const PREMIUM_TAGS = 'tags';
	const ENABLE_PER_TAG = 'per_tag';
	//const ENABLE_PER_POST = 'per_post';
	const SUBSCRIPTION_PAGE = 'sub_page';
	const SUBSCRIPTION_PAGE_REF = 'sub_page_ref';
	const SUBSCRIPTION_PAGE_SUCCESS = 'sub_page_success';
	const SUBSCRIPTION_PAGE_SUCCESS_REF = 'sub_page_success_ref';
	const PD_DENIED_MSG1 = 'pd_denied_msg1';
	const PD_DENIED_MSG2 = 'pd_denied_msg2';
	const PD_DENIED_SUB1 = 'pd_denied_sub1';
	const PD_DENIED_SUB2 = 'pd_denied_sub2';
	const PD_TYPE = 'pd_type';
	const DEFAULT_DENIED_MESSAGE = 'To continue, purchase with TinyPass';

	//settings
	const TINYPASS_PAYWALL_SETTINGS = 'tinypass_paywall_settings';

	private $data;

	public function __construct($data = null) {
		if ($data == null)
			$data = new NiceArray();


		if ($data instanceof NiceArray)
			$this->data = $data;
		else
			$this->data = new NiceArray($data);

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

	public function getPremiumTags($delimiter = null) {
		$d = $this->data->val(self::PREMIUM_TAGS, array());
		if ($delimiter && is_array($d))
			return implode($delimiter, $d);
		return $d;
	}

	public function getPremiumTagsArray() {
		$d = $this->data->val(self::PREMIUM_TAGS, array());
		if (is_array($d))
			return $d;
		return array_map('trim', explode(',', $d));
	}

	public function tagMatches($name) {
		return in_array($name, $this->getPremiumTagsArray());
	}

	public function isMode($type) {
		return $this->data->val(TPSiteSettings::MODE, TPSiteSettings::MODE_OFF) == $type;
	}

	public function getMode() {
		return $this->data->val(TPSiteSettings::MODE, TPSiteSettings::MODE_OFF);
	}

	public function isEnabledPerTag() {
		return $this->data->isValEnabled(self::ENABLE_PER_TAG);
	}

	public function isHideTeaser() {
		return $this->data->isValEnabled(self::HIDE_TEASER);
	}

	private function _isset($field) {
		return isset($this->data[$field]) && ($this->data[$field] || $this->data[$field] == 'on');
	}

	public function getResourceName() {
		return $this->data->val(self::RESOURCE_NAME, '');
	}

	public function setResourceName($s) {
		$this->data[self::RESOURCE_NAME] = $s;
	}

	public function getResourceId() {
		return $this->data->val(self::RESOURCE_ID, '');
	}

	public function setResourceId($s) {
		$this->data[self::RESOURCE_ID] = $s;
	}

	public function getNumPrices() {
		return $this->num_prices;
	}

	public function hasPriceConfig($i) {
		return $this->data->isValEnabled("po_en" . $i);
	}

	public function getPrice($i, $def = null) {
		$value = $this->data->val(self::PO_PRICE . $i);
		if ($value == null)
			return $def;
		return $value;
	}

	public function getAccess($i) {
		if ($this->getAccessPeriod($i) == null && $this->getAccessPeriodType($i) == null)
			return '';
		return $this->getAccessPeriod($i, '') . " " . $this->getAccessPeriodType($i, '');
	}

	public function getAccessFullFormat($i) {
		if ($this->getAccessPeriod($i) == null && $this->getAccessPeriodType($i) == null)
			return '';

		$price = $this->getPrice($i);
		$accessPeriod = $this->getAccessPeriod($i);
		$accessPeriodType = $this->getAccessPeriodType($i);

		if (is_numeric($price)) {
			$price = '$' . $price;
		}

		if ($this->getAccessPeriod($i) != null) {
			return "$price for $accessPeriod $accessPeriodType(s)";
		} else {
			return "$price for unlimited access";
		}
	}

	public function getAccessPeriod($i, $def = null) {
		return $this->data->val(self::PO_PERIOD . $i, $def);
	}

	public function getAccessPeriodType($i, $def = null) {
		return $this->data->val(self::PO_PERIOD_TYPE . $i, $def);
	}

	public function getCaption($i) {
		return $this->data[self::PO_CAPTION . $i];
	}

	public function getRecurring($i) {
		return $this->data->val(self::PO_RECUR . $i, '');
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

	public function getMetered($def = 'off') {
		return $this->data->val(self::METERED, $def);
	}


	public function isTimeMetered() {
		return $this->isMetered() && $this->data[self::METERED] == 'time';
	}

	public function isCountMetered() {
		return $this->isMetered() && $this->data[self::METERED] == 'count';
	}

	public function isPaymentDisplayDefault() {
		if ($this->data[self::PD_TYPE] == null)
			return TPSiteSettings::PA_EXPANDED;
		return $this->data->valEquals(self::PD_TYPE, TPSiteSettings::PA_DEFAULT);
	}

	public function isPaymentDisplayExpanded() {
		if ($this->data[self::PD_TYPE] == null)
			return TPSiteSettings::PA_EXPANDED;
		return $this->data->valEquals(self::PD_TYPE, TPSiteSettings::PA_EXPANDED);
	}

	public function getPaymentDisplay() {
		return $this->data->val(self::PD_TYPE, TPSiteSettings::PA_DEFAULT);
	}

	/**
	 * Meter fields 
	 */
	public function getMeterMaxAccessAttempts($def = null) {
		return $this->data->val(self::METER_MAX_ACCESS_ATTEMPTS, $def);
	}

	public function getMeterLockoutPeriod($def = null) {
		return $this->data->val(self::METER_LOCKOUT_PERIOD, $def);
	}

	public function getMeterLockoutPeriodType($def = null) {
		return $this->data->val(self::METER_LOCKOUT_PERIOD_TYPE, $def);
	}

	public function getMeterLockoutPeriodFull() {
		return $this->getMeterLockoutPeriod() . " " . $this->getMeterLockoutPeriodType();
	}

	public function getMeterTrialPeriod($def = null) {
		return $this->data->val(self::METER_TRIAL_PERIOD, $def);
	}

	public function getMeterTrialPeriodType($def = null) {
		return $this->data->val(self::METER_TRIAL_PERIOD_TYPE, $def);
	}

	public function getMeterTrialPeriodFull() {
		return $this->getMeterTrialPeriod() . " " . $this->getMeterTrialPeriodType();
	}

	/*
	 * Subscription releated fields
	 */

	public function getSubscriptionPage() {
		return $this->data->val(self::SUBSCRIPTION_PAGE, '');
	}

	public function getSubscriptionPageRef() {
		return $this->data->val(self::SUBSCRIPTION_PAGE_REF, '');
	}

	public function hasSubscriptionPage() {
		return $this->getSubscriptionPage() != '';
	}

	public function getSubscriptionPageSuccess() {
		return $this->data->val(self::SUBSCRIPTION_PAGE_SUCCESS, '');
	}

	public function getSubscriptionPageSuccessRef() {
		return $this->data->val(self::SUBSCRIPTION_PAGE_SUCCESS_REF, '');
	}

	public function hasSubscriptionPageSuccess() {
		return $this->getSubscriptionPageSuccess() != '';
	}

	/**
	 * Messaging
	 */
	public function getDeniedMessage1() {
		return $this->data->val(self::PD_DENIED_MSG1, self::DEFAULT_DENIED_MESSAGE);
	}

	public function getDeniedMessage2() {
		return $this->data->val(self::PD_DENIED_MSG2, "");
	}

	public function getDeniedSub1() {
		return $this->data->val(self::PD_DENIED_SUB1, self::DEFAULT_DENIED_MESSAGE);
	}

	public function getDeniedSub2() {
		return $this->data->val(self::PD_DENIED_SUB2, self::DEFAULT_DENIED_MESSAGE);
	}

	public function toArray() {
		if (isset($this->data))
			return $this->data->toArray();
		return array();
	}

}

?>