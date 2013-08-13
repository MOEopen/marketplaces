<?php
class db
{
  public $resource;
  
  function __construct()
  {
    $reg = Registry::getInstance();
    
    $this->resource = new mysqli($reg->get('config')->dbHost, $reg->get('config')->dbUser, $reg->get('config')->dbPwd, $reg->get('config')->dbName);
    $this->resource->set_charset("utf8");
  }
  
  function query($sql)
  {
    $reg->get('tools')->Save2File("Query.log", $sql);
    if($ret = $this->resource->query($sql)) {
      return $ret;
    } else {
      $reg->get('Debug')->monitor($this->resource->error);
      return false;
    }
  }
  
  function multi_query($sql)
  {
    $reg->get('tools')->Save2File("Query.log", $sql);
    if($ret = $this->resource->multi_query($sql)) {
      return $ret;
    } else {
      $reg->get('Debug')->monitor($this->resource->error);
      return false;
    }
  }
  
  function getArray($sql)
  {
    $reg->get('tools')->Save2File("Query.log", $sql);
    $ret = array();
    if ($result = $this->resource->query($sql))
    {
      while($array = $result->fetch_array(MYSQLI_ASSOC))
      {
        $ret[] = $array;
      }
    } else {
      $reg->get('Debug')->monitor("Das folgende SQL-Statement ist fehlgeschlagen:\n".$sql);
      $reg->get('Debug')->monitor($this->resource->error_list);
      return false;
    }
    return $ret;
  }
  
  function getFieldsFromTable($Table) {
    $sSql = "DESCRIBE ".$Table;
    return $this->getArray($sSql);
  }
  
  function close()
  {
    $this->resource->close();
  }
}
/*
$ArtNr = "05356003";
$db = new db();
print_r($db->buildArtikel($ArtNr));

$db->close();
*/

?>