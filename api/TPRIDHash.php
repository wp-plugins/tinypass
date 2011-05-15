<?php

class TPRIDHash {

	public static $ENCODER = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_-";
	private $hash;


	/**
	 * @rid = String
	 */
	function __construct($rid = null) {
		$this->hash = $this->hashCode($rid);
	}

	/**
	 * @return RIDHash
	 */
	public static function parse($hashCode) {
		$hash = new TPRIDHash();
		$hash->hash = $hashCode;
		return $hash;
	}

	public function getHash(){
		return $this->hash;
	}

	private static function addNum($a, $b) {
		$dif = 2147483647 - $b;
		if($dif>$a){ return $a+$b;}
		return -2147483648 + ($a-$dif) - 1;
	}

	private static function multBy31($num) {
		$res = ($num<<5) & 0xFFFFFFFF;
		if($res>=0) {
			if($num>=0) return $res-$num;
			$dif = 2147483647 + $num;
			if($dif>$res) return $res-$num;
			return -2147483648 + ($res-$dif) - 1;
		} else {
			if($num<=0) return $res-$num;
			$dif = -2147483648 + $num;
			if($dif<$res) return $res-$num;
			return 2147483647 - ($dif-$res) + 1;
		}
	}

	public static function hashCode($s) {
		if ($s == null || strlen($s) == 0) return "0";

		$h1 = 0;
		$chars =  TPRIDHash::unistr_to_ords($s);
		$h2 = count($chars) * 31;

		for ($i = 0; $i < count($chars); $i++) {
			$h1 = self::addNum(self::multBy31($h1), $chars[$i]);

			$charAt = $chars[$i];
			$adnum = self::addNum($h2, $chars[$i]);

			$mult = self::multBy31($adnum);
			$h2 = self::addNum($h2, $mult);
		}

		$bld = "";
		$bld.=(self::$ENCODER{($h1) & (0xF)});
		$bld.=(self::$ENCODER{($h1>>4) & (0x3F)});
		$bld.=(self::$ENCODER{($h1>>10) & (0x3F)});
		$bld.=(self::$ENCODER{($h1>>16) & (0x3F)});
		$bld.=(self::$ENCODER{($h1>>22) & (0x3F)});

		$bld.=(self::$ENCODER{((($h1>>28) & 0xF) | ($h2<<4)) & (0x3F)});

		$bld.=(self::$ENCODER{($h2=($h2>>2)&1073741823) & (0x3F)});


		while(($h2>>=6)!=0)
			$bld.=(self::$ENCODER{($h2 & (0x3F))});

		$leadzeros = -1;
		while(++$leadzeros<strlen($bld)){
			if($bld{$leadzeros}!='0') break;
		}

		return substr($bld, $leadzeros);
	}

	static function unistr_to_ords($str, $encoding = 'UTF-8') {
// Turns a string of unicode characters into an array of ordinal values,
// Even if some of those characters are multibyte.
		$str = mb_convert_encoding($str,"UCS-4BE",$encoding);
		$ords = array();

// Visit each unicode character
		for($i = 0; $i < mb_strlen($str,"UCS-4BE"); $i++) {
// Now we have 4 bytes. Find their total
// numeric value.
			$s2 = mb_substr($str,$i,1,"UCS-4BE");
			$val = unpack("N",$s2);
			$ords[] = $val[1];
		}
		return($ords);
	}


}

?>