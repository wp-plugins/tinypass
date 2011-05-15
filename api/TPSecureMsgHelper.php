<?php

require_once 'TPDefaultMsgBuilder.php';
require_once 'TPDefaultMsgParser.php';

class TPSecureMsgHelper {

    private $privateKey;
    private $builder;
    private $parser;

    public function __construct($privateKey) {
        $this->privateKey = null;
        $this->builder = new TPDefaultMsgBuilder();
        $this->parser = new TPDefaultMsgParser();
        $this->privateKey = $privateKey;
    }

    public function buildResource(TPResource $resource) {
        try {
            return TPSecurityUtils::encrypt($this->privateKey, $this->builder->buildResource($resource));
        } catch (Exception $e) {
            throw new Exception("Could not construct message", $e);

        }
    }

    public function buildAccessTokenList(TPAccessTokenList $list) {
        try {
            return TPSecurityUtils::encrypt($this->privateKey, $this->builder->buildAccessTokenList($list));
        } catch (Exception $e) {
            throw new RuntimeException("Could not construct message", $e);
        }
    }

    public function parseAccessTokenList($raw) {
        try {
            return $this->parser->parseAccessTokenList(TPSecurityUtils::decrypt($this->privateKey, $raw));
        } catch (Exception $e) {
            throw new Exception("Could not construct message", $e);
        }
    }

    public function parseResourceRequest($str) {
        try {
            return $this->parser->parseResourceRequest(TPSecurityUtils::decrypt($this->privateKey, $str));
        } catch (Exception $e) {
            throw new Exception("Could not construct message", $e);
        }
    }

    public function parseResourceRequests($reqString) {
        try {
            return $this->parser->parseResourceRequests(TPSecurityUtils::decrypt($this->privateKey, $reqString));
        } catch (Exception $e) {
            throw new RuntimeException("Could not construct message", $e);
        }
    }

    public function buildResourceRequest(array $resources) {
        try {
            return TPSecurityUtils::encrypt($this->privateKey, $this->builder->buildResourceRequest($resources));

        } catch (Exception $e) {
            throw new Exception("Could not construct message", $e);
        }
    }

}
?>