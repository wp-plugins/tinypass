<?php

require_once 'TPResource.php';

class TPDefaultMsgParser {

    public function parseAccessTokenList($raw) {
        $json = (array)json_decode($raw);
        $tokenList = new TPAccessTokenList();
				if(!isset($json["aid"]))
					return $tokenList;
        $tokenList->setAID($json["aid"]);
        $tokenList->setUID($json["uid"]);
        $tokenList->setBuildTime($json["built"]);

        $list = (array)$json["tokens"];
        for ($i = 0; $i < count($list); $i++) {
            $map = (array)$list[$i];
            $expire = $map["ex"];
            $ridHash = $map["rid"];
            $token = new TPAccessToken($ridHash, $expire);

            if(isset($map[TPAccessTokenList::$TRIAL_ENDTIME]))
                $token->addField(TPAccessTokenList::$TRIAL_ENDTIME, $map[TPAccessTokenList::$TRIAL_ENDTIME]);

            if(isset($map[TPAccessTokenList::$TRIAL_LOCKOUT_ENDTIME]))
                $token->addField(TPAccessTokenList::$TRIAL_LOCKOUT_ENDTIME, $map[TPAccessTokenList::$TRIAL_LOCKOUT_ENDTIME]);

            if(isset($map[TPAccessTokenList::$TRIAL_ACCESS_ATTEMPTS]))
                $token->addField(TPAccessTokenList::$TRIAL_ACCESS_ATTEMPTS, $map[TPAccessTokenList::$TRIAL_ACCESS_ATTEMPTS]);

            if(isset($map[TPAccessTokenList::$TRIAL_ACCESS_MAX_ATTEMPTS]))
                $token->addField(TPAccessTokenList::$TRIAL_ACCESS_MAX_ATTEMPTS, $map[TPAccessTokenList::$TRIAL_ACCESS_MAX_ATTEMPTS]);

            $tokenList->add($ridHash, $token);
        }

        return $tokenList;
    }

    public function parseResourceRequest($str) {
        try {
            $resource = new TPResource();
            $json = (array)json_decode($str);

            $resource->setResourceName($json["rnm"]);
            $resource->setRID( $json["rid"]);
            $resource->addTags( $json["tags"]);

            $options = (array)$json["pos"];
            for ($i = 0; $i < count($options); $i++) {
                $po = new TPPriceOption();
                $poMap = (array)$options["opt" . $i];

                $po->setPrice("" == $poMap["price"] ? null : $poMap["price"]);
                $po->setAccessPeriod("" == $poMap["exp"] ? null : $poMap["exp"]);
                $po->setStartDate("" == $poMap["sd"] ? null : $poMap["sd"]/1000);
                $po->setEndDate("" ==  $poMap["ed"] ? null : $poMap["ed"]/1000);
                $po->setCaption("" == $poMap["cpt"] ? null : $poMap["cpt"]);

                if (array_key_exists("splits", $poMap)){
                    $list = $poMap["splits"];
                    for ($j = 0; $j < count($list); $j++) {
                        $value = $list[$j];
                        $values[] = explode("=", $value);
                        $po->addSplitPay($values[0], $values[1]);
                    }

                }
                $resource->addPriceOption($po);
            }

            if(isset($json["bundle"])) {
                if(isset($json["upsell"]) && $json["upsell"]) {
                    $resource->setUpSellResource($this->parseResourceRequest($json["bundle"]));
                } else {
                    $resource->setCrossResource($this->parseResourceRequest($json["bundle"]));
                }
            }

            return $resource;
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }

    public function parseResourceRequests($str) {
        $result = array();
				$data = explode("|", $str);

				foreach($data as $raw){
					if($raw)
						array_push($result, $this->parseResourceRequest($raw));
				}
        return $result;
    }
}
