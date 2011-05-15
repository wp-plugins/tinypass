<?php

class TPJsonMsgBuilder {

	public function buildTickets(array $tickets) {
		$bld = "";
		foreach($tickets as $ticket) {
			if(strlen($bld) == 0) $bld.=("[");
			else $bld.=(",");
			$bld .= $this->buildTicket($ticket);
		}
		if(strlen($bld)>0) $bld.=("]");

		return $bld;
	}

	public function buildTicket(TPTicket $ticket) {
		$map = array();
		$map["rid"] = $ticket->getResource()->getRID();
		$map["rnm"] =  $ticket->getResource()->getResourceName();
		$map["t"] = $ticket->getTimestamp();
		$map["tags"] = $ticket->getTags();

		$pos = array();
		for ($i = 0; $i < count($ticket->getPriceOptions()); $i++) {
			$options = $ticket->getPriceOptions();
			$pos["opt" . $i] = $this->buildPriceOption($options[$i], $i);
		}

		$map["pos"] = $pos;

		if ($ticket->getBundledTicket()) {
			$map["bundle"] = $this->buildTicket($ticket->getBundledTicket());
			$map["upsell"] = $ticket->getUpSellTicket() != null;
		}

		return json_encode($map);
	}

	public function buildAccessTokenList($list) {
		if($list instanceof TPTrialAccessTokenList)
			return $this->__buildAccessTokenList($list->accessTokenList);
		else
			return $this->__buildAccessTokenList($list);
	}

	private function __buildAccessTokenList(TPAccessTokenList $list) {
		$map = array();
		$map["aid"] = $list->getAID();
		$map["uid"] = $list->getUID();
		$map["built"] = time() * 1000;

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
		$map["sd"] = $this->nuller($po->getStartDate()*1000);
		$map["ed"] = $this->nuller($po->getEndDate()*1000);
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
}

?>