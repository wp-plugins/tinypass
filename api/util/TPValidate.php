<?php

class TPValidate {

	const PRICE_FAILED_MSG = "Price must be a number or <# CUR> e.g. 1 EUR or 2.99 NOK or 1 (default USD)";
	const TIME_FAILED_MSG = "You have to specify a valid date.";
	const ACCESS_PERIOD_FAILED_MSG = 'Access Period must be a number or empty';
	const NUMBER_FAILED_MSG = ' must be a valid number';

	public static function validatePrice($price) {

		$price = trim($price);

		if (empty($price))
			return false; // Price cannot be empty

		if (preg_match('/^\d*[.,]?\d+$/', $price) || preg_match('/^\d*[.,]?\d+\s*[a-z]{3}$/i', $price))
			return true;

		return false;
	}

	public static function validateTime($time) {

		if (!empty($time) && strtotime($time) <= 0) {
			return false;
		}

		return true;
	}

	public static function validateAccessPeriod($ap) {

		if ($ap == "")
			return true;

		if (!is_numeric($ap)) {
			return false;
		}

		if ($ap < 0) {
			return false;
		}

		return true;
	}

	public static function validateNumber($num) {
		if ($num == null || $num == '' || is_numeric($num) == false)
			return false;

		return true;
	}

}

?>