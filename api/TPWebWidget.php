<?php

require_once 'TPVersion.php';
require_once 'TinyPass.php';

class TPWebWidget {

	private $tp;
	private $resources = array();
	private $callback;

	function __construct(TinyPass $tp) {
		$this->tp = $tp;
	}

	public function addResource(TPResource $resource) {
		array_push($this->resources, $resource);
		return $this;
	}

	public function setCallBackFunction($callback) {
		$this->callback = $callback;
		return $this;
	}

	public function getCode() {
		if (count($this->resources) == 0) {
			return "";
		}

		if ($this->tp->getAID() == null) throw new TinyPassException("Please provide an aid");
		$bld = "";

		$deniedAccess = false;
		foreach ($this->resources as $resource) {
			if (!$this->tp->isAccessGranted($resource)) {
				$deniedAccess = true;
				break;
			}
		}

		$bld .= "<script type=\"text/javascript\">";

		if ($deniedAccess) {

			$bld .= ("(function() {");
			$bld.=("var dp = document.createElement('script'); dp.type = 'text/javascript'; dp.async = true;");
			$bld.=("dp.src = '");

			$bld.=($this->tp->getApiEndpoint()) . (TPVersion::getPrepareURL() . "?aid=") . ($this->tp->getAID());

			$bld.=("&r=") . ($this->tp->getBuilder()->buildResourceRequest($this->resources));

			$bld .= '&ver=' . TPVersion::getVersion();

			if($this->tp->getAccessTokenList() != null)
				$bld.=("&c=") . ($this->tp->getAccessTokenList()->getRawToken());

			$bld .= ("&cb=") . $this->callback . ("';");
			$bld .= ("var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(dp, s);");
			$bld .= ("})(); ");

		}

		if ($this->tp->getTrialAccessTokenList() != null && !$this->tp->getTrialAccessTokenList()->isEmpty()) {
			$bld .= ("document.cookie='") . (TinyPass::getAppPrefix($this->tp->getAID())) . ("_TRIAL=' + '") . ($this->tp->getBuilder()->buildAccessTokenList($this->tp->getTrialAccessTokenList())) . ("' + ';expires=' + new Date(new Date().getTime() + 1000*60*60*24*90).toGMTString(); ");
		}

		$bld .= ("</script>");

		return $bld;
	}

}
?>