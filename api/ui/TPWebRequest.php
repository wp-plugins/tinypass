<?php

class TPWebRequest {

	private $ww;
	private $callback;

	public function __construct(TinyPass $tp) {
		$this->ww = new TPWebWidget($tp, $this);
	}

	public function setCallback($s) {
		$this->callback = $s;
	}

	public function getCallback() {
		return $this->callback;
	}

	/**
	 *
	 * @param <type> $tickets single ticket or an array of tickets
	 */
	public function addTicket($tickets) {
		if(is_array($tickets)) {
			foreach($tickets as $t)
				$this->ww->addTicket($t);
		}else {
			$this->ww->addTicket($tickets);
		}
	}

	public function getRequestScript() {
		try {
			return $this->ww->getCode();
		} catch (Exception $e) {
		}
		return "";
	}



}
?>