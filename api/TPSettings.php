<?php

/**
 * TPSettings
 *
 * @author tdirrenb
 */
class TPSiteSettings {

  const ENABLED = 'enabled';
  const ENV = 'env';
  const AID_SAND = 'aid_sand';
  const SECRET_KEY_SAND = 'secret_key_sand';
  const AID_PROD = 'aid_prod';
  const SECRET_KEY_PROD = 'secret_key_prod';

  private $data;

  public function __construct($arr = null) {

    if (isset($arr))
      $this->init($data);
    else {
      $this->init(array(
          TPSiteSettings::ENABLED => 'on',
          TPSiteSettings::AID_SAND => 'W7JZEZFu2h',
          TPSiteSettings::SECRET_KEY_SAND => 'jeZC9ykDfvW6rXR8ZuO3EOkg9HaKFr90ERgEb3RW',
          TPSiteSettings::AID_PROD => 'GETKEY',
          TPSiteSettings::SECRET_KEY_PROD => ('Retreive your secret key from www.tinypass.com'),
          TPSiteSettings::ENV => 0,
      ));
    }
  }

  public function toArray() {
    return $this->data;
  }

  private function init($data) {
    if ($data instanceof NiceArray)
      $this->data = $data;
    else
      $this->data = new NiceArray($data);
  }

  public function isEnabled() {
    return $this->data->valEquals(self::ENABLED, 'on');
  }

  public function isProd() {
    return !$this->isSand();
  }

  public function isSand() {
    return $this->data->valEquals(self::ENV, 0);
  }

  public function getAID() {
    if ($this->isSand()) {
      return $this->data->val(self::AID_SAND, 'KEYKEYKEY1');
    }
    return $this->data->val(self::AID_PROD, 'KEYKEYKEY1');
  }

  public function getSecretKey() {
    if ($this->isSand()) {
      return $this->data->val(self::SECRET_KEY_SAND, 'KEYKEYKEY1');
    }
    return $this->data->val(self::SECRET_KEY_PROD, 'KEYKEYKEY1');
  }

}

class NiceArray implements ArrayAccess, Iterator, Countable {

  private $data;

  public function __construct($data = null) {
    if ($data == null)
      $data = array();

    $this->data = $data;
  }

  public function offsetSet($offset, $value) {
    $this->data[$offset] = $value;
  }

  public function offsetExists($offset) {
    return isset($this->data[$offset]);
  }

  public function offsetUnset($offset) {
    unset($this->data[$offset]);
  }

  public function offsetGet($offset) {
    return isset($this->data[$offset]) ? $this->data[$offset] : null;
  }

  public function rewind() {
    reset($this->data);
  }

  public function current() {
    return current($this->data);
  }

  public function key() {
    return key($this->data);
  }

  public function next() {
    return next($this->data);
  }

  public function valid() {
    return $this->current() !== false;
  }

  public function count() {
    return count($this->data);
  }

  public function val($field, $def = null) {
    if ($this[$field] == null)
      return $def;
    return $this[$field];
  }

  public function valEquals($field, $value) {
    return isset($this[$field]) && $this[$field] == $value;
  }

  public function isValEnabled($field) {

    if ($this->val($field) == null)
      return false;
    $val = $this->val($field);

    if (is_string($val)) {
      $val = strtolower($val);
      if ($val == "true" || $val == "on")
        return true;
      if (is_numeric($val) && intval($val) > 0)
        return true;
    }else if (is_numeric($val)) {
      return $val > 0;
    } else {
      return ($val == true);
    }
    return false;
  }

}

class TPSettings {
  
}

//$ss = new TPSiteSettings();
//print_r($ss);
//echo "<br>Is enabled:" . $ss->isEnabled();
//echo "<br>Is Sand:" . $ss->isSand();
//echo "<br>Is Prod:" . $ss->isProd();
?>
