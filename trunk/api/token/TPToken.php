<?php

class TPToken {

	protected $map = array();

	const MARK_YEAR_MILLIS = 1293858000000;

	const METER_TRIAL_ENDTIME = "mtet";
	const METER_TRIAL_ACCESS_PERIOD = "mtap";

	const METER_LOCKOUT_ENDTIME = "mlet";
	const METER_LOCKOUT_PERIOD = "mlp";

	const METER_TRIAL_MAX_ACCESS_ATTEMPTS = "mtma";
	const METER_TRIAL_ACCESS_ATTEMPTS = "mtaa";
	const METER_TYPE = "mt";

	const RID = "rid";
	const EX = "ex";
	const EARLY_EX = "eex";
	const IPS = "ips";

	public function __construct($ridHash = null) {
		if($ridHash)
			$this->map[TPToken::RID] = $ridHash->toString();
	}

	public static function convertToEpochSeconds($time) {
		if ($time > TPToken::MARK_YEAR_MILLIS)
			return $time / 1000;
		return $time;
	}

	public function getValues() {
		return $this->map;
	}

	public function getRidHash() {
		return $this->map[TPToken::RID];
	}

	public function addField($s, $o) {
		$this->map[$s] = $o;
	}

	public function getField($s) {
		return $this->map[$s];
	}


	public function getFromMap($key, $defaultValue) {
		if (!array_key_exists($key, $this->map))
			return $defaultValue;
		return $this->map[$key];
	}

	public function addFields($map) {
		$this->map = array_merge($this->map, $map);
	}

}

?>
