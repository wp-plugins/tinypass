<?php

require_once 'TPAccessTokenList.php';

class TPTrialAccessTokenList {
	protected $accessTokenList;

	public function __construct($aidORAccessToken) {
		if($aidORAccessToken instanceof TPAccessTokenList)
			$this->accessTokenList = $aidORAccessToken;
		else
			$this->accessTokenList = new TPAccessTokenList($aidORAccessToken, null);

		$this->updateAccessCounts();
	}

	public function contains($rid) {
		return $this->accessTokenList->contains($rid);
	}

	public function getAccessTokenList() {
		return $this->accessTokenList;
	}

	public function setAID($aid) {
		$this->accessTokenList->setAID($aid);
	}

	private function updateAccessCounts() {
		foreach($this->accessTokenList->getTokens() as $ridHash => $token) {
			if ($token->getTrialAccessAttempts() != null && $token->getTrialAccessAttempts() > 0) {
				$token->setTrialAccessAttemptsOrig($token->getTrialAccessAttempts());
			}
		}
	}

	public function updateCount(TPResource $resource) {
		if ($resource->isTrial() && $resource->getTrialMaxAttempts() > 0) {
			if ($this->accessTokenList->contains($resource->getRID())) {
				$token = $this->accessTokenList->getToken($resource->getRID());
				if ($token->getTrialAccessAttempts() == $token->getTrialAccessAttemptsOrig()) {
					$token->setTrialAccessAttempts($token->getTrialAccessAttempts() + 1);
				}
			}
		}
	}

	public function addResource(TPResource $resource) {
		$ridhash = TPSecurityUtils::hashCode($resource->getRID());
		$token = new TPAccessToken($ridhash, 0);

		if ($resource->getTrialMaxAttempts() > 0) {
			$token->setTrialAccessAttempts(1);
			$token->setTrialAccessMaxAttempts($resource->getTrialMaxAttempts());
			$token->setTrialLockoutEndTime($resource->getTrialLockoutPeriod());
		} else {
			$token->setTrialEndTime($resource->getTrialPeriod());
			$token->setTrialLockoutEndTime($resource->getTrialLockoutPeriod());
		}

		$this->accessTokenList->add($ridhash, $token);
	}

	public function __addResource($rid, $trialEndTime, $trialLockoutEndTime) {
		$ridhash = TPSecurityUtils::hashCode($rid);
		$token = new TPAccessToken($ridhash, 0);
		$token->addField(AccessTokenList::$TRIAL_ENDTIME, $trialEndTime);
		$token->addField(AccessTokenList::$TRIAL_LOCKOUT_ENDTIME, $trialLockoutEndTime);
		$this->accessTokenList.add($ridhash, $token);
	}

	public function isLockPeriodActive($rid) {
		$accessToken = $this->accessTokenList->getToken($rid);

		if($accessToken == null)
			return false;

		$lockoutEnd = $accessToken->getTrialLockoutEndTime();

		if ($accessToken->getTrialAccessMaxAttempts() > 0) {
			return $accessToken->getTrialAccessAttempts() > $accessToken->getTrialAccessMaxAttempts() && (time() * 1000) < $lockoutEnd;
		} else {
			$trialEnd = $accessToken->getTrialEndTime();
			return (time()*1000) > $trialEnd && (time()*1000) < $lockoutEnd;
		}

	}

	public function isTrialPeriodActive($rid) {
		$accessToken = $this->accessTokenList->getToken($rid);

		if($accessToken == null)
			return false;

		if ($accessToken->getTrialAccessMaxAttempts() > 0) {
			return $accessToken->getTrialAccessAttempts() <= $accessToken->getTrialAccessMaxAttempts();
		} else {
			$expires = $accessToken->getTrialEndTime();
			return $expires != null && (time() * 1000) < $expires;
		}
	}

	public function isEmpty() {
		return count($this->accessTokenList->getTokens()) == 0;
	}
}
?>