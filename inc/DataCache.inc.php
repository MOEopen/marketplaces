<?php
class tru_datacache {
  private $CachePath = 'tmp/';
  private $CacheFilePrefix = "Cache_";
  private $Extention = ".txt";
  
  
  
  public function getCachedData($CacheId) {
    return false;
    $Filename = $this->CachePath . $this->CacheFilePrefix . $CacheId . $this->Extention;
    $handle = @fopen($Filename, 'r');
    if ($handle) {
      $json = '';
      while (($buffer = fgets($handle, 4096)) !== false) {
        $json .= $buffer;
      }
      //monitor($json);
      //monitor(json_decode($json, true));
      return json_decode($json, true);
    } else {
      return false;
    }
  }
  
  public function cacheData($CacheId, $Data) {
    return false;
    $Filename = $this->CachePath . $this->CacheFilePrefix . $CacheId . $this->Extention;
    $FilePointer = fopen($Filename, 'w');
    fputs ($FilePointer, json_encode($Data));
    fclose($FilePointer);
  }
  

}