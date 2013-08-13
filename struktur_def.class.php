<?php
class tru_struktur_def {
  private $aStruktur    = array();
  private $aMatchFields = array ();
  
  // public $ChannelID;
  
  function __construct() {
    require_once('tools.class.php');
    $this->tools = new tru_tools();

    require_once('db.php');
    $this->DB = new db();
    
    require_once('DataCache.inc.php');
    $this->DataCache = new tru_datacache();
  }
  
  public function GetMasterStruktur(){
    
    if (empty($this->aMasterStruktur)) {
      
      $this->aMasterStruktur = $this->DataCache->getCachedData('MasterStruktur');
      if($this->aMasterStruktur  != false) return $this->aMasterStruktur;
      
      $ChannelID = $this->ChannelID;
      $sSqlWhere = "WHERE `ChannelID` = {$ChannelID} ";
      
      $sSqlStruktur = "SELECT * FROM  `tru_channel_strukur_def` {$sSqlWhere} ORDER BY `Sort` ASC;";
      // echo $sSqlStruktur;
      $aStrukturRaw = $this->DB->getArray($sSqlStruktur);
      foreach ($aStrukturRaw as $val) {
        if(empty($val['Options']) AND $val['Options'] == '') {
          $val['Options'] = array();
        } else {
          $val['Options'] = explode(",", $val['Options']);
        }
        $this->aMasterStruktur['Fields'][$val['AmazonFeld']] = $val;
      }
      
      $this->DataCache->cacheData('MasterStruktur', $this->aMasterStruktur);
    }
    return $this->aMasterStruktur;
  }
  
  public function getRawStruktur($parent_child) {
    if (empty($this->aRawStruktur[$parent_child])) {
    
      $this->aRawStruktur[$parent_child] = $this->DataCache->getCachedData('RawStruktur_'.$parent_child);
      if($this->aRawStruktur[$parent_child]  != false) return $this->aRawStruktur[$parent_child];
      
      switch ($parent_child) {
        case 'Parent':
          $FieldName = 'DefaultParent';
          break;
        case 'Child':
          $FieldName = 'DefaultChild';
          break;
        default:
          die("Beim Aufruf der Funktion getRawStruktur wurde der falsche Parameter übergeben, möglich sind 'Parent' oder 'Child'");
      }
      $aStruktur = $this->getMasterStruktur();
      foreach($aStruktur['Fields'] as $key => $value) {
        
        $this->aRawStruktur[$parent_child][$key] = $value[$FieldName];
      }
      $this->DataCache->cacheData('RawStruktur_'.$parent_child, $this->aRawStruktur[$parent_child]);
    }
    return $this->aRawStruktur[$parent_child];
  }

  public function getMatchFields() {
    if (empty($this->aMatchFields)) {
      
      $this->aMatchFields = $this->DataCache->getCachedData('MatchFields');
      if($this->aMatchFields  != false) return $this->aMatchFields;
      
      $aStruktur = $this->GetMasterStruktur();
      foreach($aStruktur['Fields'] as $key=>$value) {
        if($value['MatchOxarticleParent'] != "") $this->aMatchFields['Parent'][$key] = $value['MatchOxarticleParent'];
        if($value['MatchOxarticleChild']  != "") $this->aMatchFields['Child'][$key]  = $value['MatchOxarticleChild'];
      }
      $this->DataCache->cacheData('MatchFields', $this->aMatchFields);
    }
    return $this->aMatchFields;
  }
  
  public function getMatchFieldsSqlSnippet($parent_child) {
    if (empty($this->aSqlSnippetMatchfields[$parent_child])) {
      
      $this->aSqlSnippetMatchfields[$parent_child] = $this->DataCache->getCachedData('SqlSnippetMatchfields'.$parent_child);
      if($this->aSqlSnippetMatchfields[$parent_child]  != false) return implode(", ", $this->aSqlSnippetMatchfields[$parent_child]);
      
      $aMatchFields = $this->getMatchFields();
      foreach ($aMatchFields[$parent_child] as $key => $value) {
        $value = str_replace(".", "`.`", $value);
        $this->aSqlSnippetMatchfields[$parent_child][] = "`{$value}` AS `{$key}`";
      }
      $this->DataCache->cacheData('SqlSnippetMatchfields'.$parent_child, $this->aSqlSnippetMatchfields[$parent_child]);
    }
    return implode(", ", $this->aSqlSnippetMatchfields[$parent_child]);
  }
  
}