<?php
class TPMeter {

	private $accessToken;

	public function __construct(TPAccessToken $accessToken) {
		$this->accessToken = $accessToken;
	}

	/**
	 * Create a Meter based upon the number of views
	 * @param name name of the meter that can be stored as a cookie
	 * @param maxViews max number of views allowed before meter expires
	 * @param lockoutPeriod period the user will be locked out after the meter expires
	 * @return a new meter
	 */
	public static function createViewBased($name, $maxViews, $lockoutPeriod) {
		$accessToken = new TPAccessToken(new TPRID($name));
		$accessToken->getTokenData()->addField(TPTokenData::METER_TYPE, TPTokenData::METER_REMINDER);
		$accessToken->getTokenData()->addField(TPTokenData::METER_TRIAL_MAX_ACCESS_ATTEMPTS, $maxViews);
		$accessToken->getTokenData()->addField(TPTokenData::METER_TRIAL_ACCESS_ATTEMPTS, 0);
		$accessToken->getTokenData()->addField(TPTokenData::METER_LOCKOUT_PERIOD, $lockoutPeriod);
		return new TPMeter($accessToken);
	}

	public static function createTimeBased($name, $trialPeriod, $lockoutPeriod) {

		$accessToken = new TPAccessToken(new TPRID($name));

		$accessToken->getTokenData()->addField(TPTokenData::METER_TYPE, TPTokenData::METER_REMINDER);

		$trialPeriodParsed = TPUtils::parseLoosePeriodInSecs($trialPeriod);
		$lockoutPeriodParsed = TPUtils::parseLoosePeriodInSecs($lockoutPeriod);
		$trialEndTime = TPUtils::now() + $trialPeriodParsed;
		$lockoutEndTime = $trialEndTime + $lockoutPeriodParsed;

		$accessToken->getTokenData()->addField(TPTokenData::METER_TRIAL_ENDTIME, $trialEndTime);
		$accessToken->getTokenData()->addField(TPTokenData::METER_LOCKOUT_ENDTIME, $lockoutEndTime);

		return new TPMeter($accessToken);

	}

	public function increment() {
		$value = $this->accessToken->getTokenData()->getFromMap(TPTokenData::METER_TRIAL_ACCESS_ATTEMPTS, 0) + 1;

		if ($this->accessToken->getMeterType() != TPTokenData::METER_STRICT && $this->isMeterViewBased()) {
			if ($this->getTrialViewCount() == $this->getTrialViewLimit()) {
				$this->accessToken->getTokenData()->addField(
								TPTokenData::METER_LOCKOUT_ENDTIME,
								TPUtils::now() + TPUtils::parseLoosePeriodInSecs($this->accessToken->getTokenData()->getField(TPTokenData::METER_LOCKOUT_PERIOD)));
			}
		}
		$this->accessToken->getTokenData()->addField(TPTokenData::METER_TRIAL_ACCESS_ATTEMPTS, $this->getTrialViewCount() + 1);
		return $value;
	}

	public function isTrialPeriodActive() {
		return $this->accessToken->isTrialPeriodActive();
	}

	public function isLockoutPeriodActive() {
		return $this->accessToken->isLockoutPeriodActive();
	}

	public function getData() {
		return $this->accessToken->getTokenData();
	}

	public function isMeterViewBased() {
		return $this->accessToken->isMeterViewBased();
	}


	public function getTrialViewCount() {
		return $this->accessToken->getTrialViewCount();
	}

	public function getTrialViewLimit() {
		return $this->accessToken->getTrialViewLimit();
	}


	public function _isTrialDead() {
		return $this->accessToken->_isTrialDead();
	}

	public function getMeterType() {
		return $this->accessToken->getMeterType();
	}

	public function getLockoutEndTimeSecs() {
		return $this->accessToken->getLockoutEndTimeSecs();
	}

}
