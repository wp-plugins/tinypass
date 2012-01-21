<?php

class TPMeterDetails extends TPTokenWrapper {

	const REMINDER = 10;
	const STRICT = 20;

	public static function createWithHash($ridHash, $type = 20) {
		$details = new TPMeterDetails();
		$details->token->addField(TPToken::RID, $ridHash->toString());
		$details->token->addField(TPToken::METER_TYPE, $type);
		return $details;
	}

	public static function createWithToken($token) {
		$details = new TPMeterDetails();
		$details->token = $token;
		return $details;
	}

	public static function createWithValues($ridHash, $map) {
		$details = new TPMeterDetails();
		$details->token->addField(TPToken::RID, $ridHash->toString());
		$details->token->addFields($map);
		return $details;
	}

	public function __construct($token = null) {
		parent::__construct();
		if($token instanceof TPToken && $token)
			$this->token = $token;
		else if($token instanceof TPRIDHash && $token)
			$this->token->addField(TPToken::RID, $token->toString());
	
	}

	public function isTrialPeriodActive() {
		if ($this->isStrict()) {
			$expires = $this->getTrialEndTime();
			if ($expires == null || $expires == 0)
				return false;
			return time() <= $expires;
		} else {
			if ($this->isCountBased()) {
				return $this->getTrialAccessAttempts() <= $this->getTrialMaxAccessAttempts();
			} else {
				$expires = $this->getTrialEndTime();
				if ($expires == null || $expires == 0)
					return true;
				return time() <= $expires;
			}
		}
	}

	public function isLockout() {
		$expires = $this->getLockoutEndTimeSecs();

		if ($this->isTrialPeriodActive())
			return false;

		if ($expires == null || $expires == 0)
			return false;

		return time() <= $expires;
	}

	public function isDead() {
		return $this->isLockout() == false && $this->isTrialPeriodActive() == false;
	}

	public function getTrialEndTime() {
		return $this->token->getFromMap(TPToken::METER_TRIAL_ENDTIME, 0);
	}

	public function setTrialEndTime($l) {
		$this->token->addField(TPToken::METER_TRIAL_ENDTIME, TPToken::convertToEpochSeconds($l));
	}

	public function getLockoutEndTimeSecs() {
		return $this->token->getFromMap(TPToken::METER_LOCKOUT_ENDTIME, 0);
	}

	public function setLockoutEndTimeSecs($l) {
		$this->token->addField(TPToken::METER_LOCKOUT_ENDTIME, TPToken::convertToEpochSeconds($l));
	}

	public function setLockoutPeriod($s) {
		$this->token->addField(TPToken::METER_LOCKOUT_PERIOD, $s);
	}

	public function getTrialAccessAttempts() {
		return $this->token->getFromMap(TPToken::METER_TRIAL_ACCESS_ATTEMPTS, 0);
	}

	public function setTrialAccessAttempts($l) {
		$this->token->addField(TPToken::METER_TRIAL_ACCESS_ATTEMPTS, $l);
	}

	public function getTrialMaxAccessAttempts() {
		return $this->token->getFromMap(TPToken::METER_TRIAL_MAX_ACCESS_ATTEMPTS, 0);
	}

	public function setTrialMaxAccessAttempts($l) {
		$this->token->addField(TPToken::METER_TRIAL_MAX_ACCESS_ATTEMPTS, $l);
	}

	public function getType() {
		return $this->token->getFromMap(TPToken::METER_TYPE, TPMeterDetails::STRICT);
	}

	public function setType($type) {
		$this->token->addField(TPToken::METER_TYPE, $type);
	}

	protected function isStrict() {
		return $this->getType() == TPMeterDetails::STRICT;
	}

	public function toString() {
		$sb = "";
		$sb.("\nTrialDetails");
		$sb.("\n\tEnd Time:" . $this->getTrialEndTime() . " in " .  date('D, d M Y H:i:s' , $this->getTrialEndTime()));
		$sb.("\n\tLockout End Time:" . $this->getLockoutEndTimeSecs() . " in " . date('D, d M Y H:i:s' , $this->getLockoutEndTimeSecs()));
		$sb.("\n\tMax Attempts:" . $this->getTrialMaxAccessAttempts());
		$sb.("\n\tAttempts:" . $this->getTrialAccessAttempts());
		$sb.("\n\tType:" . $this->getType());
		return $sb;
	}

	public static function is($token) {
		return array_key_exists(TPToken::METER_TYPE,  $token->getValues());
	}

	public function touch() {
		try {
			if ($this->isStrict() == false && $this->isCountBased()) {
				if ($this->getTrialAccessAttempts() == $this->getTrialMaxAccessAttempts()) {
					$this->token->addField(
									TPToken::METER_LOCKOUT_ENDTIME,
									time() + 
									(TPPriceOption::parseLoosePeriod( $this->token->getField(TPToken::METER_LOCKOUT_PERIOD))/1000)
													);
				}
			}
			$this->token->addField(TPToken::METER_TRIAL_ACCESS_ATTEMPTS, $this->getTrialAccessAttempts() + 1);
		} catch (Exception $e) {
			throw new TPTokenUnparseable("Could not update trial information:" . $e);
		}

	}

	public function isCountBased() {
		return array_key_exists(TPToken::METER_TRIAL_MAX_ACCESS_ATTEMPTS, $this->token->getValues());
	}

}


?>