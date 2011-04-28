<?php

require_once 'TPSecureMsgHelper.php';

class TPMockSecureMsgHelper extends TPSecureMsgHelper {

    private $builder;
    private $parser;

    public function __construct($privateKey) {
        parent::__construct($privateKey);
        $this->builder = new TPDefaultMsgBuilder();
        $this->parser = new TPDefaultMsgParser();
    }

    public function buildResource(TPResource $resource) {
        try {
//            return urlencode($str) URLEncoder.encode(builder.buildResource(resource), "UTF-8");
            return urlencode(utf8_encode($this->builder->buildResource($resource)));
        } catch (Exception $e) {
            throw new Exception("Could not construct message", $e);
        }
    }

    public function buildAccessTokenList(TPAccessTokenList $list) {
        try {
            return urlencode(utf8_encode($this->builder->buildAccessTokenList($list)));
        } catch (Exception $e) {
            throw new Exception("Could not construct message", $e);
        }
    }

    public function parseAccessTokenList($raw) {
        try {
            return $this->parser->parseAccessTokenList(utf8_decode(urldecode($raw)));
        } catch (Exception $e) {
            throw new Exception("Could not construct message", $e);
        }
    }

    public function parseResourceRequest($str) {
        try {
            return $this->parser->parseResourceRequest(utf8_decode(urldecode($str)));
        } catch (Exception $e) {
            throw new Exception("Could not construct message", $e);
        }
    }

    public function parseResourceRequests($reqString) {
        try {
            return $this->parser->parseResourceRequests(utf8_decode(urldecode($reqString)));
        } catch (Exception $e) {
            throw new Exception("Could not construct message", $e);
        }
    }

    public function buildResourceRequest(array $resources) {
        try {
            return urlencode(utf8_encode($this->builder->buildResourceRequest($resources)));
        } catch (Exception $e) {
            throw new Exception("Could not construct message", $e);
        }
    }

}
