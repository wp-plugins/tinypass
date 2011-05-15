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
			$data.= ("&r=").($this->tp->getBuilder()->buildTickets($deniedTickets));
			if($this->tp->getAccessTokenList() != null) $data.=("&c=").($this->tp->getAccessTokenList()->getRawToken());
		}

		if(strlen($data) > 1700) {
			//$guid = substr(mt_rand().time(), 0, 10);
			$guid = TPSecurityUtils::genRandomString();
			$jsonp = $this->prepareJSONP("&guid=" . $guid);
			$code.=(
							"var tinypassform = document.createElement('div'); " .
											"tinypassform.style.position = 'absolute'; " .
											"tinypassform.style.visibility = 'hidden'; " .
											"tinypassform.style.top = '-100px'; " .
											"tinypassform.style.left = '-100px'; " .
											"tinypassform.style.width = '0'; " .
											"tinypassform.style.height = '0'; " .
											"tinypassform.innerHTML = \"<iframe onload=\\\"var t=document.getElementById('state").($guid).("'); if(t==null || t.value!='ok') return; ").($jsonp).("\\\" id=\\\"TinyPass").($guid).("\\\" name=\\\"TinyPass").($guid).("\\\" style=\\\"width:0;height:0;border:0\\\"></iframe>" .
											"<form id=\\\"TinyPassForm").($guid).("\\\" action=\\\"").($this->tp->getApiEndpoint()).(TPVersion::getDataURL())
							.("\\\" target=\\\"TinyPass").($guid).("\\\" method=\\\"post\\\"><textarea name=\\\"data\\\">").($data)
							.(
							"</textarea>" .
											"<input type=\\\"hidden\\\" name=\\\"guid\\\" value=\\\"").($guid).("\\\">" .
											"<input type=\\\"hidden\\\" id=\\\"state").($guid).("\\\" value=\\\"").($guid).("\\\">" .
											"</form>\"; " .
											"var bodyel = document.getElementsByTagName('body')[0]; " .
											"if(bodyel.childNodes.length>0) {" .
											"bodyel.insertBefore(tinypassform,bodyel.childNodes[0]); " .
											"} else {" .
											"bodyel.appendChild(tinypassform); " .
											"} " .
											"document.getElementById('TinyPassForm").($guid).("').submit(); document.getElementById('state").($guid).("').value='ok';"
			);

		} else {
			$code.=($this->prepareJSONP($data));
		}

		if ($this->tp->getTrialAccessTokenList() != null && !$this->tp->getTrialAccessTokenList()->isEmpty()) {
			$code .= ("document.cookie='") . (TinyPass::getAppPrefix($this->tp->getAID())) . ("_TRIAL=' + '") . ($this->tp->getBuilder()->buildAccessTokenList($this->tp->getTrialAccessTokenList())) . ("' + ';expires=' + new Date(new Date().getTime() + 1000*60*60*24*90).toGMTString(); ");
		}

		return "<script type=\"text/javascript\">(function() { " . $code . " })(); </script>";
	}

	private function prepareJSONP($reqString) {
		$code = "";
		$code.=("var dp = document.createElement('script'); dp.type = 'text/javascript'; dp.async = true;");
		$code.=("dp.src = '");

		$code.=($this->tp->getApiEndpoint()).(TPVersion::getPrepareURL()).("?aid=").($this->tp->getAID());

		$code.=($reqString);

		$code.=("&cb=").($this->callback);

		$code.= "&v=".TPVersion::$VERSION;
		$code.= "&l=" . urlencode(phpversion());
		$code.=("';");
		$code.=("var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(dp, s);");

		return $code;
	}

}
?>