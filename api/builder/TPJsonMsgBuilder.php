<?php

class TPJsonMsgBuilder {

	public function buildTicketRequest(array $tickets) {
		$list = array();

		foreach($tickets as $ticket) {
			$list[] = $this->buildTicket($ticket);
		}

		return json_encode($list);
	}

	public function buildTicket(TPRequest $ticket) {

		$ticketMap = array();

		$ticketMap["o1"] = $this->buildOffer($ticket->getPrimaryOffer());
		$ticketMap["t"] =  $ticket->getTimestampSecs();
		$ticketMap["tags"] =  $ticket->getTags();
		$ticketMap["v"] =  $ticket->getVersion();
		$ticketMap["ip"] =  $ticket->getClientIP();


		if ($ticket->getOptions() != null && count($ticket->getOptions()) > 0)
			$ticketMap["opts"] =  $ticket->getOptions();

		if ($ticket->getSecondaryOffer() != null) {
			$ticketMap["o2"] = $this->buildOffer($ticket->getSecondaryOffer());
		}


		return $ticketMap;

//		$ticketMap["rid"] = $ticket->getResource()->getRID();
//		$ticketMap["rnm"] =  $ticket->getResource()->getResourceName();
//		$ticketMap["t"] = $ticket->getTimestamp()*1000;
//		$ticketMap["tags"] = $ticket->getTags();

//		$pos = array();
//		for ($i = 0; $i < count($ticket->getPriceOptions()); $i++) {
//			$options = $ticket->getPriceOptions();
//			$pos["opt" . $i] = $this->buildPriceOption($options[$i], $i);
//		}
//		$ticketMap["pos"] = $pos;

	}

	private function buildOffer(TPOffer $offer, $options = array()) {

		$map = array();
		$map["rid"] = $offer->getResource()->getRID();
		$map["rnm"] = $offer->getResource()->getName();
		$map["rurl"] = $offer->getResource()->getURL();

		if ($options != null)
			$map["opts"] = $options;

		$pos = array();
		$priceOptions = $offer->getPricing()->getPriceOptions();
		for ($i = 0; $i < count($priceOptions); $i++)
			$pos["opt".($i)] = $this->buildPriceOption($priceOptions[$i], $i);
		$map["pos"] = $pos;

		$pol = array();
		$policies = $offer->getPolicies();
		foreach ($policies as $policy ) {
			$pol[] = $policy->toMap();
		}
		$map["pol"] = $pol;

		return $map;

	}

	public function buildAccessTokenList(TPAccessTokenList $list) {
		$map = array();
		$map["aid"] = $list->getAID();
		$map["uid"] = $list->getUID();
		$map["built"] = time();
		$map["ips"] = $list->getIPs();


		$tokens = array();

		foreach($list->getTokens() as $rid => $token) {
			array_push($tokens, $token->getValues());
		}

		$map["tokens"] = $tokens;
		return json_encode($map);
	}

	private function nuller($value) {
		return $value != null ? $value : "";
	}

	private function buildPriceOption(TPPriceOption $po, $index) {
		$map = array();
		$map["price"] = $this->nuller($po->getPrice());
		$map["exp"] = $this->nuller($po->getAccessPeriod());

		if ($po->getStartDateInSecs() != null && $po->getStartDateInSecs() != 0)
			$map["sd"] =  $this->nuller($po->getStartDateInSecs());

		if ($po->getEndDateInSecs() != null && $po->getEndDateInSecs() != 0)
			$map["ed"] = $this->nuller($po->getEndDateInSecs());


		$map["cpt"] =  $this->nuller($po->getCaption());

		if (count($po->getSplitPays()) > 0) {
			$splits = array();
			foreach($po->getSplitPays() as $email => $amount) {
				array_push($splits, "$email=$amount");
			}
			$map["splits"] = $splits;
		}
		return $map;
	}

	public function parseAccessTokenList($raw) {
		$json = (array)json_decode($raw);
		$tokenList = new TPAccessTokenList();

		$tokenList->setAID($json["aid"]);
		$tokenList->setUID($json["uid"]);
		$tokenList->setBuildTime($json["built"]);

		$ips = array();
		if(isset($json["ips"]))
			$ips = $json["ips"];
		$tokenList->setIPs($ips);

		$list = (array)$json["tokens"];
		for ($i = 0; $i < count($list); $i++) {
			$map = (array)$list[$i];


			if(!isset($map[TPToken::RID]))
				$ridHash = TPRIDHash::parse("");
			else
				$ridHash = TPRIDHash::parse($map["rid"]);

			$token = new TPToken($ridHash);


			$fields = array(
							TPToken::EX,
							TPToken::EARLY_EX,
							TPToken::METER_TRIAL_ENDTIME,
							TPToken::METER_LOCKOUT_PERIOD,
							TPToken::METER_LOCKOUT_ENDTIME,
							TPToken::METER_TRIAL_ACCESS_ATTEMPTS,
							TPToken::METER_TRIAL_MAX_ACCESS_ATTEMPTS,
							TPToken::METER_TYPE,
			);


			foreach($fields as $f) {
				if(isset($map[$f]))
					$token->addField($f, $map[$f]);
			}

			$tokenList->add($ridHash, $token);
		}

		return $tokenList;
	}


}

?>
