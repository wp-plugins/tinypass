<?php

class TPDefaultMsgBuilder {

	public function buildResourceRequest(array $resources) {
		$bld = "";
		foreach($resources as $resource) {
			$bld .= $this->buildResource($resource) . "|";
		}
		return $bld;
	}

	public function buildResource(TPResource $resource) {
		$map = array();
		$map["rid"] = $resource->getRID();
		$map["rnm"] =  $resource->getResourceName();
		$map["t"] = $resource->getTimestamp();
		$map["tags"] = $resource->getTags();

		$pos = array();
		for ($i = 0; $i < count($resource->getPriceOptions()); $i++) {
			$options = $resource->getPriceOptions();
			$pos["opt" . $i] = $this->buildPriceOption($options[$i], $i);
		}

		$map["pos"] = $pos;
	
		if ($resource->getBundledResource()) {
			$map["bundle"] = $this->buildResource($resource->getBundledResource());
			$map["upsell"] = $resource->isUpSell();
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