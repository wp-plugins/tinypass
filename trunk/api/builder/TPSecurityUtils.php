<?php

class TPSecurityUtils {

	//private static $LONG_MAX = "9223372036854775807";
	//private static $LONG_MIN = "-9223372036854775808";

	public static function hashCode($s) {
		if($s == null || strlen($s) == 0) return "0";

		$hash = "0";
		$h = "0";
		if (bccomp($h,"0")==0) {
			$off = "0";
			$val =  TPSecurityUtils::unistr_to_ords($s);
			$len = count($val);
			for ($i = 0; $i < $len; $i++) {
				$temp = $h;
				for($j=0; $j<30; $j++) {
					$temp = TPSecurityUtils::addNums($temp, $h);
//				echo "---------> :" . $temp. "\n";
				}
				$h = TPSecurityUtils::addNums($temp, $val[$off++]);
//			echo "MAIN:" . $h. "\n";
			}
			$hash = $h;
		}
		return $hash;
	}

	static function unistr_to_ords($str, $encoding = 'UTF-8') {
// Turns a string of unicode characters into an array of ordinal values,
// Even if some of those characters are multibyte.
		$str = mb_convert_encoding($str,"UCS-4BE",$encoding);
		$ords = array();

// Visit each unicode character
		for($i = 0;
		$i < mb_strlen($str,"UCS-4BE");
		$i++) {
// Now we have 4 bytes. Find their total
// numeric value.
			$s2 = mb_substr($str,$i,1,"UCS-4BE");
			$val = unpack("N",$s2);
			$ords[] = $val[1];
		}
		return($ords);
	}

	public static function encrypt($keyString,  $value) {
		if(strlen($keyString) > 32)
			$keyString = substr($keyString, 0, 32);
		if (strlen($keyString) < 32)
			$keyString = str_pad($keyString, 32, 'X');

		$cipher = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
		$iv =  '1234567812345678';
		$key192 = $keyString;
		$cleartext = $value;

		if (mcrypt_generic_init($cipher, $key192, $iv) != -1) {
			$blockSize = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
			$padding   = $blockSize - (strlen($cleartext) % $blockSize);
			$cleartext .= str_repeat(chr($padding), $padding);
// PHP pads with NULL bytes if $cleartext is not a multiple of the block size..
			$cipherText = mcrypt_generic($cipher,$cleartext );
			mcrypt_generic_deinit($cipher);
			mcrypt_module_close($cipher);
			return TPSecurityUtils::urlensafe(($cipherText));
		}
		return TPSecurityUtils::urlensafe(($value));

	}

	public static function urlensafe($data) {
		return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
	}

	public static function urldesafe($data) {
		return base64_decode(strtr($data, '-_', '+/'));
	}

	public static function decrypt($keyString, $data) {
		$data = (TPSecurityUtils::urldesafe($data));
		if(strlen($keyString) > 32)
			$keyString = substr($keyString, 0, 32);
		if (strlen($keyString) < 32)
			$keyString = str_pad($keyString, 32, 'X');

//		$data = pack('H*', $data);

		$iv =  '1234567812345678';
		$cipher = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');

		if (mcrypt_generic_init($cipher, $keyString, $iv) != -1) {

			$cipherText =  mdecrypt_generic ($cipher , $data);

			mcrypt_generic_deinit($cipher);
			mcrypt_module_close($cipher);

			$endCharVal = ord(substr( $cipherText, strlen( $cipherText)-1, 1 ));
			if ( $endCharVal <= 16 && $endCharVal >= 0 ) {
				$cipherText = substr($cipherText, 0, 0-$endCharVal); //Remove the padding (ascii value == ammount of padding)
			}

			return $cipherText;
		}


	}

	static function genRandomString($length = 10) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
		$string = '';

		for ($p = 0; $p < $length; $p++) {
			$string .= $characters[mt_rand(0, strlen($characters)-1)];
		}

		return $string;
	}
}

?>