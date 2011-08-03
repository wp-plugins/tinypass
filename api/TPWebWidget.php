<?php

require_once 'TPVersion.php';
require_once 'TinyPass.php';

class TPWebWidget {

	private $tp;
	private $tickets = array();
	private $callback;

	function __construct(TinyPass $tp) {
		$this->tp = $tp;
	}

	public function addTicket(TPTicket $ticket) {
		array_push($this->tickets, $ticket);
		return $this;
	}

	public function setCallBackFunction($callback) {
		$this->callback = $callback;
		return $this;
	}

	public function getTicketLink($ticket, $onclick = true) {
		$bld = "";
		$bld.=($this->tp->getApiEndpoint()).(TPVersion::getAuthURL()).("?aid=").($this->tp->getAID());
		$bld.=("&r=").($this->tp->getBuilder()->buildTicket($ticket));
		$bld.=("&display=page");
		if($onclick){
			$bld = ' href="#" onclick="window.open(\'' . $bld . '\', \'popup\', \'scrollbars=1,status=0,toolbar=0,width=650,height=420,resizable=1,location=1\');return false;" ';
		}
		return $bld;
	}

	public function getCode() {
		if (count($this->tickets) == 0) {
			return "";
		}

		if ($this->tp->getAID() == null) throw new TinyPassException("Please provide an aid");


		$deniedTickets = array();
		foreach ($this->tickets as $ticket) {
			if (!$this->tp->isAccessGranted($ticket->getResource())) {
				if($ticket->getUpSellTicket()!=null && $this->tp->isAccessGranted($ticket->getUpSellTicket()->getResource()))
					continue;
				$deniedTickets[] = $ticket;
			}
		}


		$code = "";
		$data = "";

		if (count($deniedTickets) > 0) {
			$data.=("&r=").($this->tp->getBuilder()->buildTickets($deniedTickets));
			if($this->tp->getAccessTokenList() != null) $data.=("&c=").($this->tp->getAccessTokenList()->getRawToken());

			$template = file_get_contents(dirname(__FILE__) . '/widget.html');
			$template = preg_replace('/\${guid}/', TPSecurityUtils::genRandomString(), $template);
			$template = preg_replace('/\${endpoint}/', $this->tp->getApiEndpoint(), $template);
			$template = preg_replace('/\${prepare_link}/', TPVersion::getPrepareURL(), $template);
			$template = preg_replace('/\${data_link}/', TPVersion::getDataURL(), $template);
			$template = preg_replace('/\${aid}/', $this->tp->getAID(), $template);
			$template = preg_replace('/\${data}/', $data, $template);
			$template = preg_replace('/\${listener}/', urlencode($this->callback), $template);
			$template = preg_replace('/\${version}/', TPVersion::getVersion(), $template);
			//$template = preg_replace('/\${lang}/', urlencode("php-".phpversion()), $template);
			//$template = preg_replace('/\${os}/', urlencode(php_uname('s') . php_uname('r')), $template);
			return $template;
		}

		return "";
	}
}
?>
