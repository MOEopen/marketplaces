<?php
class Registry {
  protected static $instance = null;
  protected $values = array();
  
  public static function getInstance() {
    if ( self::$instance == null ) {
      self::$instance = new Registry();
    } 
    return self::$instance;
  }
  
  protected function __construct() {}
  
  private function __clone() {}
  
  public function set($key, $value) {
    $this->values[$key] = $value;
  }
  
  public function get($key) {
    if ( isset($this->values[$key]) ) {
      return $this->values[$key];
    }
    return null;
  }
}