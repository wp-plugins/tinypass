<?php

class TinyPassException extends Exception {

	public function __construct($message) {
		parent::__construct($message);
	}

}

class TPTokenUnparseable extends Exception {

	public function __construct($message) {
		parent::__construct($message);
	}

}
?>
