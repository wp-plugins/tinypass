<?php

require_once 'TPSecurityUtils.php';
require_once 'TinyPassException.php';

class TPResource {

	protected $rid;
	protected $rname;

	private $isTrial = false;

	protected $trialPeriod = 0;
	protected $trialMaxAttempts = -1;
	protected $trialLockoutPeriod = 0;

	function __construct($rid = null, $rname = null) {
		$this->rid = $rid;
		$this->rname = $rname;
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

	public function isTrial() {
		return $this->isTrial;
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

	public function getTrialMaxAttempts() {
		return $this->trialMaxAttempts;
	}

	public function getTrialLockoutPeriod() {
		return $this->trialLockoutPeriod;
	}

}
