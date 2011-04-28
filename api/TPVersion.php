<?php

class TPVersion {

    public static $VERSION = "1.0";

    public static $CONTEXT = "/v1";

		public static function getPrepareURL(){
			return TPVersion::$CONTEXT  .  TinyPass::$API_PREPARE ;
		}

		public static function getVersion(){
			return TPVersion::$VERSION;
		}

}

?>
