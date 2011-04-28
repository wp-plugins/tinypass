<?php

require_once 'TPMockSecureMsgHelper.php';

class TPClientMsgBuilder {

    private $sb;

    function __construct($privateKey) {
        $this->sb = new TPSecureMsgHelper($privateKey);
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
            $accessTokenList = $this->sb->parseAccessTokenList($token);
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

    public function buildResourceRequest($resources) {
        return $this->sb->buildResourceRequest($resources);
    }

    public function buildAccessTokenList($tokentList) {
        return $this->sb->buildAccessTokenList($tokentList->getAccessTokenList());
    }

}
?>