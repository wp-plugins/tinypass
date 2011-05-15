<?php

require_once 'TPMockSecureMsgHelper.php';
require_once dirname(__FILE__) . '/builder/TPBuilderFactory.php';

class TPClientMsgBuilder {

	private $builder;
	private $privateKey;

	function __construct($privateKey) {
		$this->privateKey = $privateKey;
		$this->builder = new TPBuilderFactory($privateKey);
	}

	public function parseToken($aid, array $cookies, $cookieName) {
		if (($cookies == null) || (count($cookies) == 0)) return new TPAccessTokenList();
		$cookieName = TinyPass::getAppPrefix($aid) . $cookieName;
		$token = null;

		foreach ($cookies as $name => $value) {
			if ($name == $cookieName) {
				$token = $value;
				break;
			}
		}

//        $token = urldecode($token);

		if (($token != null) && (count($token) > 0)) {
			$parser = new TPParserFactory($this->privateKey, $token);
			$accessTokenList = $parser->parseAccessTokenList($token);
			$accessTokenList->setRawToken($token);
			return $accessTokenList;
		}

		return new TPAccessTokenList($aid, null);
	}


	public function parseTrailTokenList($aid, array $cookies) {
		return new TPTrialAccessTokenList($this->parseToken($aid, $cookies, TinyPass::$TRIAL_COOKIE_SUFFIX));
	}

	public function parseAccessTokenList($aid, array $cookies) {
		return $this->parseToken($aid, $cookies, TinyPass::$COOKIE_SUFFIX);
	}

	public function buildTickets($resources) {
		return $this->builder->buildTickets($resources);
	}

	public function buildAccessTokenList($tokentList) {
		return $this->builder->buildAccessTokenList($tokentList->getAccessTokenList());
	}

}
?>