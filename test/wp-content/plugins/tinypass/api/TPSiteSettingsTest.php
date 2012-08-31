<?php

require_once dirname(__FILE__) . '/../../../../../api/TPSettings.php';

class TPSiteSettingsTest extends PHPUnit_Framework_TestCase {

	public function testSettings() {

		$ss = new TPSiteSettings();

		//defaults
		$this->assertEquals(true, $ss->isEnabled());
		$this->assertEquals(true, $ss->isSand());
		$this->assertEquals("W7JZEZFu2h", $ss->getAID());
		$this->assertEquals("jeZC9ykDfvW6rXR8ZuO3EOkg9HaKFr90ERgEb3RW", $ss->getSecretKey());

		$ss->isEnabled()
		$this->assertEquals("W7JZEZFu2h", $ss->getAID());
		$this->assertEquals("jeZC9ykDfvW6rXR8ZuO3EOkg9HaKFr90ERgEb3RW", $ss->getSecretKey());
	}

}

?>
