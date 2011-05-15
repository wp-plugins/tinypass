<?php

class TPVersion {

    public static $VERSION = "1.3";

    public static $CONTEXT = "/v1";

		public static function getPrepareURL(){
			return TPVersion::$CONTEXT  .  TinyPass::$API_PREPARE ;
		}

		public static function getDataURL(){
			return TPVersion::$CONTEXT  .  TinyPass::$API_DATA;
		}

		public static function getVersion(){
			return TPVersion::$VERSION;
		}

}

?>
