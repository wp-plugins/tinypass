<?php
register_activation_hook(__FILE__, 'tinypass_activate');
register_deactivation_hook(__FILE__, 'tinypass_deactivate');
register_uninstall_hook(__FILE__, 'tinypass_uninstall');

function tinypass_activate() {
 
  $error = '';
  if (!extension_loaded('mbstring'))
    $error .= "&nbsp;&nbsp;&nbsp;<a href=\"http://php.net/manual/en/ref.mbstring.php\">mbstring php module</a> is required for Tinypass<br>";
  if (!extension_loaded('mcrypt'))
    $error .= "&nbsp;&nbsp;&nbsp;<a href=\"http://php.net/manual/en/book.mcrypt.php\">mcrypt php module</a> is required for Tinypass<br>";

  if (version_compare(PHP_VERSION, '5.2.0') < 0) {
    $error .= "&nbsp;&nbsp;&nbsp;Requires PHP 5.2+";
  }

  if ($error)
    die('Tinypass could not be enabled<br>' . $error);

  $old = get_option("tinypass_setting");
  if ($old && count($old)) {
    $message = "Upgrading from Tinypass version 1.x to 2.x is considered a significant upgrade.<br>";
    $message .= "<br>Please contact support@tinypass.com if you are having migration issues or questions";
    $message .= "<br><br>You can restore your previous version by manually downloading latest 1.4.x plugin at http://wordpress.org/extend/plugins/tinypass/developers";
    $message .= "<br><br>You can manually upgrade by uninstalling the Tinypass plugin and then performing a brand new install.  All your existing settings will be lost!!";
    die($message);
  }

  $data = get_plugin_data(plugin_dir_path(__FILE__) . "/tinypass.php");
  $version = $data['Version'];
  update_option('tinypass_version', $version);

  //tinypass_include();
  //$storage = new TPStorage();
  //$ss = new TPSiteSettings();
  //$storage->saveSiteSettings($ss);
}

function tinypass_deactivate() {
  //$storage = new TPStorage();
  //$ss = $storage->getSiteSettings();
  //$ss->setEnabled(0);
  //$storage->saveSiteSettings($ss);
}

function tinypass_uninstall() {
  
}

?>