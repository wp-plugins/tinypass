<?php

class TPVersion {

    public static $VERSION = "1.4";

    public static $CONTEXT = "/v1";

		public static function getPrepareURL(){
			return TPVersion::$CONTEXT  .  TinyPass::$API_PREPARE ;
		}

		public static function getDataURL(){
			return TPVersion::$CONTEXT  .  TinyPass::$API_DATA;
		}

		public static function getAuthURL(){
			return TPVersion::$CONTEXT  .  TinyPass::$API_AUTH;
		}

		public static function getVersion(){
			return TPVersion::$VERSION;
		}

}
?>