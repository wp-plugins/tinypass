<?php

class TPWebWidget {

	private $tp;
	private $wr;
	private $tickets = array();

	function __construct(TinyPass $tp, TPWebRequest $wr) {
		$this->tp = $tp;
		$this->wr= $wr;
	}

	public function addTicket(TPTicket $ticket) {
		$this->tickets[] = $ticket;
		if ($this->tp->isClientIPValid())
			$ticket->setClientIP($this->tp->getClientIP());
	}

	public function getCode() {

		$deniedTickets = array();
		foreach ($this->tickets as $ticket) {
			if (!$this->tp->isAccessGranted($ticket->getPrimaryOffer())) {
				$deniedTickets[] = $ticket;
			}
		}

		$template = "";
		$code = "";
		$data = "";
		$init = false;

		if (count($deniedTickets) > 0) {

			$init = true;
			$data = "";
			$data.=("&r=").preg_replace('/"/', "\"", $this->tp->getMsgBuilder()->buildTicketRequest($deniedTickets));
			if ($this->tp->getAccessTokenList() != null) $data.=("&c=").($this->tp->getAccessTokenList()->getRawToken());

			$template = file_get_contents(dirname(__FILE__) . '/widget.html');
			$template = preg_replace('/\${guid}/', TPSecurityUtils::genRandomString(), $template);
			$template = preg_replace('/\${endpoint}/', $this->tp->getApiEndpoint(), $template);
			$template = preg_replace('/\${prepare_link}/', TPVersion::getPrepareURL(), $template);
			$template = preg_replace('/\${data_link}/', TPVersion::getDataURL(), $template);
			$template = preg_replace('/\${aid}/', $this->tp->getAID(), $template);
			$template = preg_replace('/\${data}/', $data, $template);
			$template = preg_replace('/\${listener}/', ($this->wr->getCallback() ? urlencode($this->wr->getCallback()) : ""), $template);
			$template = preg_replace('/\${version}/', TPVersion::getVersion(), $template);

		}

		if ($this->tp->__hasLocalTokenChanges()) {
			$init = true;
			$s = "<script> document.cookie= '" . $this->tp->__generateLocalCookie() . " ;path=/;expires=' + new Date(new Date().getTime() + 1296000000).toGMTString();</script>";
			$template .= $s;
		}

		if ($init)
			return $template;

		return "";
	}
}
?>
