<?php

class TPJsonMsgParser {

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
			$ridHash = TPRIDHash::parse($map["rid"]);
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

	public function parseTicket($str) {
		try {
			$resource = new TPResource();
			$ticket = new TPTicket($resource);
			if(is_string($str))
				$json = (array)json_decode($str);
			else
				$json = (array)$str;

			$resource->setResourceName($json["rnm"]);
			$resource->setRID( $json["rid"]);
			$ticket->addTags( $json["tags"]);

			$options = (array)$json["pos"];
			for ($i = 0; $i < count($options); $i++) {
				$po = new TPPriceOption();
				$poMap = (array)$options["opt" . $i];

				$po->setPrice("" == $poMap["price"] ? null : $poMap["price"]);
				$po->setAccessPeriod("" == $poMap["exp"] ? null : $poMap["exp"]);
				$po->setStartDate("" == $poMap["sd"] ? null : $poMap["sd"]/1000);
				$po->setEndDate("" ==  $poMap["ed"] ? null : $poMap["ed"]/1000);
				$po->setCaption("" == $poMap["cpt"] ? null : $poMap["cpt"]);

				if (array_key_exists("splits", $poMap)) {
					$list = $poMap["splits"];
					for ($j = 0; $j < count($list); $j++) {
						$value = $list[$j];
						$values[] = explode("=", $value);
						$po->addSplitPay($values[0], $values[1]);
					}

				}
				$ticket->addPriceOption($po);
			}

			if(isset($json["bundle"])) {
				if(isset($json["upsell"]) && $json["upsell"]) {
					$ticket->setUpSell($this->parseTicket($json["bundle"]));
				} else {
					$ticket->setCrossSell($this->parseTicket($json["bundle"]));
				}
			}

			return $ticket;
		} catch (Exception $e) {
			throw new Exception($e);
		}
	}

	public function parseTickets($str) {
		$result = array();
		$jsonArray = json_decode($str);

		foreach($jsonArray as $rawTicket) {
			array_push($result, $this->parseTicket($rawTicket));
		}
		return $result;
	}
}
