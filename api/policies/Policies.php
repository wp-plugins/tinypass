<?php

class TPMeteredPolicy {

	public static function createMeteredByPeriod($trialPeriod, $lockoutPeriod) {
		return TPStrictMeteredPricing::createByPeriod($trialPeriod, $lockoutPeriod);
	}

	public static function createReminderByPeriod($trialPeriod, $lockoutPeriod) {
		return TPReminderMeteredPricing::createByAccessPeriod($trialPeriod, $lockoutPeriod);
	}

	public static function createReminderByAccessCount($trialCount, $lockoutPeriod) {
		return TPReminderMeteredPricing::createByAccessCount($trialCount, $lockoutPeriod);
	}
}

class TPMeteredPolicyImpl extends TPPolicy {

}
class TPReminderMeteredPricing extends TPMeteredPolicyImpl {

	public static function createByAccessCount($trialCount, $lockoutPeriod) {

		$meter = new TPReminderMeteredPricing();

		$meter->set(TPToken::METER_LOCKOUT_PERIOD, $lockoutPeriod);
		$meter->set(TPPolicy::POLICY_TYPE, TPPolicy::REMINDER_METER_BY_COUNT);
		$meter->set(TPToken::METER_TYPE, TPMeterDetails::REMINDER);
		$meter->set(TPToken::METER_TRIAL_MAX_ACCESS_ATTEMPTS, $trialCount);
		$meter->set(TPToken::METER_TRIAL_ACCESS_ATTEMPTS, 0);
		return $meter;

	}

	public static function createByAccessPeriod($trialPeriod, $lockoutPeriod) {

		$meter = new TPReminderMeteredPricing();

		$meter->set(TPToken::METER_TRIAL_ACCESS_PERIOD, $trialPeriod);
		$meter->set(TPToken::METER_LOCKOUT_PERIOD, $lockoutPeriod);
		$meter->set(TPToken::METER_TYPE, TPMeterDetails::REMINDER);
		$meter->set(TPPolicy::POLICY_TYPE, TPPolicy::REMINDER_METER_BY_TIME);

		$parsed = TPPriceOption::parseLoosePeriod($trialPeriod);
		$trialEndTime = time() + $parsed;
		$meter->set(TPToken::METER_TRIAL_ENDTIME, TPToken::convertToEpochSeconds($trialEndTime));
		$meter->set(TPToken::METER_LOCKOUT_ENDTIME, TPToken::convertToEpochSeconds($trialEndTime + TPPriceOption::parseLoosePeriod($lockoutPeriod)));
		return $meter;
	}
}

/**
 * Metered Pricing
 */
class TPStrictMeteredPricing extends TPMeteredPolicyImpl {

	public static function createByPeriod($trialPeriod, $lockoutPeriod) {
		$meter = new TPStrictMeteredPricing();
		$meter->set(TPToken::METER_TRIAL_ACCESS_PERIOD, $trialPeriod);
		$meter->set(TPToken::METER_LOCKOUT_PERIOD, $lockoutPeriod);
		$meter->set(TPToken::METER_TYPE, TPMeterDetails::STRICT);
		$meter->set(TPPolicy::POLICY_TYPE, TPPolicy::STRICT_METER_BY_TIME);
		return $meter;
	}

}

class TPDiscountPolicy extends TPPolicy {


	public static function onTotalSpendInPeriod($amount, $withInPeriod, $discount) {
		$d = new TPDiscountPolicy();
		$d->set(TPPolicy::POLICY_TYPE, TPPolicy::DISCOUNT_TOTAL_IN_PERIOD);
		$d->set("amount", $amount);
		$d->set("withinPeriod", $withInPeriod);
		$d->set("discount", $discount);
		return $d;
	}

	public static function previousPurchased(array $rids, $discount) {
		$d = new TPDiscountPolicy();
		$d->set(TPPolicy::POLICY_TYPE, TPPolicy::DISCOUNT_PREVIOUS_PURCHASE);
		$d->set("rids", $rids);
		$d->set("discount", $discount);
		return d;

	}
}

class TPRestrictionPolicy extends TPPolicy {

	public static function limitPurchasesInPeriodByAmount($maxAmount, $withInPeriod, $linkWithDetails = null) {

		$r = new TPRestrictionPolicy();
		$r->set(TPPolicy::POLICY_TYPE, TPPolicy::RESTRICT_MAX_PURCHASES);
		$r->set("amount", $maxAmount);
		$r->set("withInPeriod", $withInPeriod);
		if($linkWithDetails)
			$r->set("linkWithDetails", $linkWithDetails);

		return $r;
	}


}


?>