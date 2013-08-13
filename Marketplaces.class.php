<?php
class tru_Marketplaces {
  public  $DB;
  public  $struktur;
  private $aAttributes;
  private $aAktiveArticles = array();
  private $aArticleStatusFlat = array();
  
  // private $OutputCharset = 'UTF-8';
  private $OutputCharset = 'Windows-1252';
  public  $Tbl_Article_Status = 'tru_channel_article_status';
  private $Tbl_Article_Attributes = 'tru_channel_article_attributes';
  private $ChannelID = 1;
  
  public  $OXSHOPID = "1";
  public  $OXLOCATION = "de";
  
  function __construct() {
    require_once('tools.class.php');
    $this->tools = new tru_tools();
    
    require_once('db.php');
    $this->DB = new db();

    require_once('struktur_def.class.php');
    $this->struktur = new tru_struktur_def();
    $this->struktur->ChannelID = $this->getChannelID();
    // echo $this->struktur->ChannelID;
    
    require_once('DataCache.inc.php');
    $this->DataCache = new tru_datacache();
    
    require_once('Marketplaces.deleTmp.class.php');
    $this->DeleteTmp = new tru_DeleteTmp;
    
    $this->getChannelSettings();
    
    // $this->tools->monitor($this->struktur->getMasterStruktur());
    // $this->tools->monitor($this->struktur->getMatchFields());
    // $this->tools->monitor($this->struktur->getMatchFieldsSqlSnippet('Parent'));
    // $this->tools->monitor($this->struktur->getMatchFieldsSqlSnippet('Child'));
    // $this->tools->monitor($this->struktur->getRawStruktur('Parent'));
    // $this->tools->monitor($this->struktur->getRawStruktur('Child'));
    
    // $this->tools->monitor($_REQUEST);
    // $this->tools->monitor($this->getAttributes());
  }
  
  public function getChannelSettings() {
    $sql ="SELECT * FROM `tru_channel_def` WHERE `ID` = {$this->getChannelID()};";
    $aChannelSettings = $this->DB->getArray($sql);
    $this->aChannelSettings = $aChannelSettings[0];
    $this->aChannelSettings['RowSeparator']   = $this->ConvertASCIIs($this->aChannelSettings['RowSeparator']);
    $this->aChannelSettings['FieldSeparator'] = $this->ConvertASCIIs($this->aChannelSettings['FieldSeparator']);
    $this->aChannelSettings['TextSeparator']  = $this->ConvertASCIIs($this->aChannelSettings['TextSeparator']);
    // $this->tools->monitor($this->aChannelSettings, true);
  }
  
  public function Export() {
    // $this->getAktiveArticle();
    // $this->tools->monitor($this->aAktiveArticles);
    
    $this->enrichArticle();
    // $this->tools->monitor($this->aAllProducts);
    
    // $this->BuildParent();
    // $this->tools->monitor($this->aAllProducts);
    // $this->BuildChild();
    // $this->tools->monitor($this->aAllProducts);

    $this->SortArray();
    // $this->tools->monitor($this->aAllProducts);
    
    $this->ReworkArray();
    // $this->tools->monitor($this->aAllProducts);

    $this->Convert2Csv();
    $this->Save2File();
  }
  
  public function enrichArticle() {
    // Sicherstellen, dass die MasterStruktur geladen ist.
    if ( empty($this->struktur->aMasterStruktur) ) $this->struktur->getMasterStruktur();
    // $this->tools->monitor($this->struktur->aMasterStruktur);
    
    // Sicherstellen, das die aktiven Artikel geladen sind
    if ( empty($this->aAktiveArticles) ) $this->getAktiveArticle();
    // $this->tools->monitor($this->aAktiveArticles);
    
    // Sicherstellen, dass die Attribute geladen sind
    if ( empty($this->aAttributes) ) $this->getAttributes();
    // $this->tools->monitor($this->aAttributes);
    
    foreach ($this->aAktiveArticles as $OXID_Mod => $aArticle_Mod) {
      // Daten von oxarticle an Rohstruktur anfügen
      $aExportMod = array_merge($this->struktur->getRawStruktur('Parent'), $this->getArticleFromOxid($aArticle_Mod['OXID'], $aArticle_Mod['COPYFROM'], 'Parent'));
      // $this->tools->monitor($aExportMod);
      
      // Attribute überschreiben
      if ( @is_array($this->aAttributes[$OXID_Mod]) ) {
        $aExportMod = @array_merge($aExportMod, $this->aAttributes[$OXID_Mod]);
      }
      // $this->tools->monitor($aExportMod);
      
      // An Ausgabe Array anfügen
      $this->aAllProducts[$OXID_Mod] = $aExportMod;
      
      // Zwischenaufbau der Artikelebene
      foreach ( $aArticle_Mod['Child'] as $OXID_Art => $aArticle_Art) {
        // Daten von oxarticle an Rohstruktur anfügen
        $aExportArticle = $aArtOxid = array_merge($this->struktur->getRawStruktur('Parent'), $this->getArticleFromOxid($aArticle_Art['OXID'], $aArticle_Art['COPYFROM'], 'Parent'));
        // $this->tools->monitor($aExportArticle);
        
        // Attribute von Übergeordnetem Modell anfügen
        if ( @is_array($this->aAttributes[$OXID_Mod]) ) {
          $aExportArticle = @array_merge($aExportArticle, $this->aAttributes[$OXID_Mod]);
        }
        // $this->tools->monitor($aExportArticle);
        
        // Attribute von Artikel anfügen
        if ( @is_array($this->aAttributes[$OXID_Art]) ) {
          $aExportArticle = @array_merge($aExportArticle, $this->aAttributes[$OXID_Art]);
        }
        // $this->tools->monitor($aExportArticle);
        
        // Aufbau der Größenebene
        foreach ($aArticle_Art['Child'] AS $OXID_Groesse => $aArticle_Groesse) {
          
          // Daten von oxarticle an Rohstruktur anfügen
          $aExportGroesse = array_merge($this->struktur->getRawStruktur('Child'), $this->getArticleFromOxid($aArticle_Groesse['OXID'], $aArticle_Groesse['COPYFROM'], 'Child'));
          $aExportGroesse['parent-sku'] = $OXID_Mod;
          
          // #######################
          // Überschreibe $aChildArticle mit Werten des Parent falls Feld gefüllt
          foreach ($aArtOxid AS $ParentKeys => $ParentValue) {
            if( !empty($ParentValue) AND $this->struktur->aMasterStruktur['Fields'][$ParentKeys]['inheritable'] == true ) $aExportGroesse[$ParentKeys] = $ParentValue;
          }
          // $this->tools->monitor($aExportGroesse); 
          // #######################
          
          // Attribute von Übergeordnetem Modell anfügen
          if ( @is_array($this->aAttributes[$OXID_Mod]) ) {
            $aExportGroesse = @array_merge($aExportGroesse, $this->aAttributes[$OXID_Mod]);
          }
          // Attribute von Übergeordnetem Artikel anfügen
          if ( @is_array($this->aAttributes[$OXID_Art]) ) {
            $aExportGroesse = @array_merge($aExportGroesse, $this->aAttributes[$OXID_Art]);
          }
          // $aExportGroesse['product-name'] = $aExportGroesse['product-name'] . ', ' . $aExportGroesse['color'] . ' ' . $aExportGroesse['size'];
          // Attribute von Groesse anfügen
          if ( @is_array($this->aAttributes[$OXID_Groesse]) ) {
            $aExportGroesse = @array_merge($aExportGroesse, $this->aAttributes[$OXID_Groesse]);
          }
          // $this->tools->monitor($aExportGroesse);
          
          // An Ausgabe Array anfügen
          $this->aAllProducts[$OXID_Groesse] = $aExportGroesse;
        }
        
      }
    }
  }
  
  public function getArticleFromOxid($OXID, $COPYFROM, $Parent_Child) {
    $_OXID = $OXID;
    if ( $COPYFROM != '' ) $_OXID = $COPYFROM;
    $sSqlArticleFromOxid  = "SELECT {$this->struktur->getMatchFieldsSqlSnippet($Parent_Child)} ".PHP_EOL;
    $sSqlArticleFromOxid .= "FROM `oxarticles` LEFT JOIN `oxartextends` ON `oxarticles`.`OXID` = `oxartextends`.`OXID` ".PHP_EOL;
    $sSqlArticleFromOxid .= "WHERE `oxarticles`.`OXID` = '{$_OXID}';";
    // $this->tools->monitor($sSqlArticleFromOxid);
    $ret = $this->DB->getArray($sSqlArticleFromOxid);
    if ( $COPYFROM != '' ) $ret[0]['sku'] = $OXID;
    // $this->tools->monitor($ret[0]);
    return $ret[0];
  }
  
  public function getArticleIdsFromOxid($OXID) {
    $sSqlArticleFromOxid  = "SELECT `OXID`, `OXPARENTID`, `OXTITLE` ".PHP_EOL;
    $sSqlArticleFromOxid .= "FROM `oxarticles` ".PHP_EOL;
    $sSqlArticleFromOxid .= "WHERE `oxarticles`.`OXID` LIKE '{$OXID}%' AND `OXPARENTID` != '';";
    // $this->tools->monitor($sSqlArticleFromOxid);
    $ret = $this->DB->getArray($sSqlArticleFromOxid);
    // $this->tools->monitor($ret[0]);
    return $ret;
  }
  
  public function getArticleIdsFromOxartnum($OXID) {
    $sSqlArticleFromOxid  = "SELECT `OXID`, `OXPARENTID`, `OXTITLE` ".PHP_EOL;
    $sSqlArticleFromOxid .= "FROM `oxarticles` ".PHP_EOL;
    $sSqlArticleFromOxid .= "WHERE `oxarticles`.`OXARTNUM` LIKE '{$OXID}%' AND `OXPARENTID` != '';";
    // $this->tools->monitor($sSqlArticleFromOxid);
    $ret = $this->DB->getArray($sSqlArticleFromOxid);
    // $this->tools->monitor($ret[0]);
    return $ret;
  }
  
  public function getAktiveArticle() {
    if ( empty($this->aAktiveArticles) ) {
      
      $this->aAktiveArticles = $this->DataCache->getCachedData('AktiveArticles');
      if ( $this->aAktiveArticles != false ) return $this->aAktiveArticles;
      
      $this->aAktiveArticles = $this->getAktiveArticleRecurs();
      
      $this->DataCache->cacheData('AktiveArticles', $this->aAktiveArticles);
    }
    return $this->aAktiveArticles;
  }
  
  public function getAllDbArticle() {
    if ( empty($this->aAllDbArticles) ) {
      
      $this->aAllDbArticles = $this->DataCache->getCachedData('AllDbArticles');
      if ( $this->aAllDbArticles != false ) return $this->aAllDbArticles;
      
      $this->aAllDbArticles = $this->getAktiveArticleRecurs('', 1, '');
      
      $this->DataCache->cacheData('AllDbArticles', $this->aAllDbArticles);
    }
    return $this->aAllDbArticles;
  }
  
  public function getArticleStatus($OXID = '') {
    $sSqlArticleStatus = "SELECT DISTINCT * FROM `{$this->Tbl_Article_Status}`";
    if ( $OXID != '' ) $sSqlArticleStatus .= "WHERE `ChannelID` = '{$this->getChannelID()}' AND `OXID` = '{$OXID}'";
    $sSqlArticleStatus .= ";";
    $aArticleStatus = $this->DB->getArray($sSqlArticleStatus);
    foreach ( $aArticleStatus AS $aArticle ) {
      $aArticleStatusNew[$aArticle['OXID']] = $aArticle;
    }
    return $aArticleStatusNew;
  }
  
  private function getAktiveArticleRecurs($Parent = '', $Level = 1, $Status = 2) {
    $aAllAktiveArticle = array();
    $sSqlAktiveArticle = "SELECT DISTINCT * FROM `{$this->Tbl_Article_Status}` WHERE `ChannelID` = '{$this->getChannelID()}' AND `PARENTID` = '{$Parent}'";
    if ( $Status >= 1 AND $Status <= 5 ) {
      $sSqlAktiveArticle .= " AND `STATUS` = {$Status}";
    }
    $sSqlAktiveArticle .= ";";
    $aAktiveArticles = $this->DB->getArray($sSqlAktiveArticle);
    if ( !empty($aAktiveArticles) ) {
      // $this->tools->monitor($aAktiveArticles);
      foreach ( $aAktiveArticles as $aAktiveArticle ) {
        $aAllAktiveArticle[$aAktiveArticle['OXID']] = $aAktiveArticle;
        $aAllAktiveArticle[$aAktiveArticle['OXID']]['Level'] = $Level;
        
        $aAllAktiveArticle[$aAktiveArticle['OXID']]['Child'] = $this->getAktiveArticleRecurs($aAktiveArticle['OXID'], $Level+1, $Status);
        // Unterdrückt das leere Child
        // $aChild = $this->getAktiveArticleRecurs($aAktiveArticle['OXID'], $Level+1, $Status);
        // if ( !empty($aChild) ) $aAllAktiveArticle[$aAktiveArticle['OXID']]['Child'] = $aChild;
        
        // $this->tools->monitor($aAllAktiveArticle);
      }
    }
    // if ( empty($aAllAktiveArticle) ) return;
    return $aAllAktiveArticle;
  }
  
  public function getSingleArticleAttributes($OXID) {
    if ( empty($this->aAttributes) ) $this->getAttributes();
    if ( empty($this->aAttributes[$OXID]) ) return array();
    return $this->aAttributes[$OXID];
  }
  
  public function getAttributes() {
    if (empty($this->aAttributes)) {

      $this->aAttributes = $this->DataCache->getCachedData('Attributes');
      if($this->aAttributes  != false) return $this->aAttributes;

      $sql = "SELECT `OXOBJECTID`, `TYPE`, `VALUE` FROM `{$this->Tbl_Article_Attributes}` WHERE `ChannelID` = '{$this->getChannelID()}' AND `OXSHOPID` = '1' AND `OXLOCATION` = 'de';";
      $aAttributesRaw = $this->DB->getArray($sql);
      foreach ($aAttributesRaw as $value) {
        $this->aAttributes[$value['OXOBJECTID']][$value['TYPE']] = $value['VALUE'];
      }
      $this->DataCache->cacheData('Attributes', $this->aAttributes);
    }
    return $this->aAttributes;
  }
  
  public function BuildParent() {
    /*******************************************
     Parents aufbauen
    ********************************************/

    // Sicherstellen, dass die Attribute geladen sind
    $this->getAttributes();

    //$EOL = "\n";
    $EOL = PHP_EOL;
    //$EOL = "";
    $sSqlParent  = "SELECT {$this->struktur->getMatchFieldsSqlSnippet('Parent')} ".$EOL;
    $sSqlParent .= "FROM `oxarticles` LEFT JOIN `oxartextends` ON `oxarticles`.`OXID` = `oxartextends`.`OXID` ".$EOL;
    $sSqlParent .= "WHERE `oxarticles`.`OXID` IN ".$EOL."(".$EOL;
    $sSqlParent .= "  SELECT DISTINCT `oxarticles`.`OXPARENTID` ".$EOL;
    $sSqlParent .= "  FROM `tru_channel_article_status` INNER JOIN `oxarticles` ON `tru_channel_article_status`.`OXID` = `oxarticles`.`OXID` ".$EOL;
    $sSqlParent .= "  WHERE `tru_channel_article_status`.`STATUS` = 2 ".$EOL;
    $sSqlParent .= ");";
    // $this->tools->monitor($sSqlParent);
    $aParentArticlesDB = $this->DB->getArray($sSqlParent);
    // $this->tools->monitor($aParentArticleDB);

    // Baue das Array für die Parents auf
    foreach ($aParentArticlesDB as $value) {
      // Hole die Struktur aus der Datenbank, gefüllt mit den Default Werten
      $aTmp = $this->struktur->getRawStruktur('Parent');
      // Fülle das Array mit den Daten aus der oxarticle und oxartextends
      foreach ($value as $key2=>$value2) {
        $aTmp[$key2] = $value2;
      }
      // Fülle das Array mit den Werten aus tru_channel_article_attributes und überschreibe diese falls schon gefüllt
      foreach ($this->aAttributes[$value['sku']] as $AttributeName => $sAttributeValue) {
        $aTmp[$AttributeName] = $sAttributeValue;
      }
      // $this->tools->monitor($aTmp);
      
      $this->aPrentIDs[$value['sku']] = $aTmp;
      $this->aAllProducts[$value['sku']] = $aTmp;
    }
    unset($aTmp, $value, $value2, $key2);
    // $this->tools->monitor($this->aPrentIDs);
    // $this->tools->monitor($this->aAllProducts);
  }
  
  public function BuildChild() {
    /*******************************************
     Childs aufbauen
    ********************************************/
    
    // Sicherstellen, dass die Attribute geladen sind
    $this->getAttributes();
    
    // Sicherstellen, dass die MasterStruktur geladen ist.
    $this->struktur->getMasterStruktur();
    
    // Artikel aus oxarticle die in tru_channel_article_status den Staus 2 haben (übertragen)
    // Artikelinformationen aus oxarticle der Felder die Matchfelder definiert sind
    $sSqlChild = "SELECT {$this->struktur->getMatchFieldsSqlSnippet('Child')} FROM `oxarticles` WHERE `OXID` IN (";
    $sSqlChild .= "SELECT DISTINCT oxarticles.OXID
    FROM tru_channel_article_status INNER JOIN oxarticles ON tru_channel_article_status.OXID = oxarticles.OXID
    WHERE tru_channel_article_status.STATUS=2 AND tru_channel_article_status.ChannelID = '{$this->getChannelID()}'";
    $sSqlChild .= ");";
    // $this->tools->monitor($sSqlChild);
    
    $aChildArticlesDB = $this->DB->getArray($sSqlChild);
    // $this->tools->monitor($aChildArticlesDB);

    // Durchlaufe alle Artikel des SQL oben
    foreach ($aChildArticlesDB as $aChildArticleDB) {
      // $this->tools->monitor($aChildArticleDB);
      
      // RawStruktur mit Default Werten
      $aChildArticle = $this->struktur->getRawStruktur('Child');
      // $this->tools->monitor($aChildArticle);
      
      // Überschreibe $aChildArticle mit Werten aus oxarticle. Achtung! Wenn Wert definiert ist aber nicht gefüllt wird mit NULL überschrieben!
      $aChildArticle = array_merge($aChildArticle, $aChildArticleDB);
      // $this->tools->monitor($aChildArticle);

      // Überschreibe $aChildArticle mit Werten des Parent falls Feld gefüllt
      foreach ($this->aPrentIDs[$aChildArticleDB['parent-sku']] AS $ParentKeys => $ParentValue) {
        if( !empty($ParentValue) AND $this->struktur->aMasterStruktur['Fields'][$ParentKeys]['inheritable'] == true ) $aChildArticle[$ParentKeys] = $ParentValue;
      }
      // $this->tools->monitor($aChildArticle);
      
      // Überschreibe $aChildArticle mit Werten der tru_channel_article_attributes. Achtung! Wenn Wert definiert ist aber nicht gefüllt wird mit NULL überschrieben!
      if ( !empty( $this->aAttributes[$aChildArticle['sku']] ) ) {
        $aChildArticle = array_merge($aChildArticle, $this->aAttributes[$aChildArticle['sku']]);
        // $this->tools->monitor($this->aAttributes[$aChildArticle['sku']]);
      }
      // $this->tools->monitor($aChildArticle);

      $this->aAllProducts[$aChildArticle['sku']] = $aChildArticle;
    }
  }
  
  public function SortArray() {
    asort($this->aAllProducts);
  }
  
  public function Convert2Csv() {
    $sFieldSeparator = (string)$this->aChannelSettings['FieldSeparator'];
    $sRowSeparator   = (string)$this->aChannelSettings['RowSeparator'];
    $sTextSeparator  = $this->aChannelSettings['TextSeparator'];
    // $this->CsvOutput = $this->tools->array_to_csv($this->aAllProducts, $this->aChannelSettings['HeaderCount'], "\t", "\n", '');
    $this->CsvOutput = $this->tools->array_to_csv($this->aAllProducts, $this->aChannelSettings['HeaderCount'], $this->aChannelSettings['FieldSeparator'], $this->aChannelSettings['RowSeparator'], $this->aChannelSettings['TextSeparator']);
  }
  
  public function Save2File() {
    $FilePath = "tmp/";
    $Extention = ".txt";
    $Timestamp = strftime("%Y-%m-%d_%H-%M-%S", time());
    $this->Filename = $FilePath . "Amazon_Export_" . $Timestamp . $Extention;
    $FilePointer = fopen($this->Filename, 'w');
    // $this->CsvOutput = "TemplateType=Clothing	Version=2012.0924	This row for Amazon.com use only.  Do not modify or delete.																																																																																																																											\n".$this->CsvOutput;
    if ( !empty($this->aChannelSettings['PreHeader']) )  $this->CsvOutput = $this->aChannelSettings['PreHeader'].$this->aChannelSettings['RowSeparator'].$this->CsvOutput;
    if ( !empty($this->aChannelSettings['PostFooter']) ) $this->CsvOutput = $this->aChannelSettings['RowSeparator'].$this->aChannelSettings['PostFooter'].$this->CsvOutput;

    $this->convertOutput();
    // fwrite ($FilePointer, pack("CCC",0xef,0xbb,0xbf));
    fwrite ($FilePointer, $this->CsvOutput);
    fclose($FilePointer);
  }
  
  
  public function ReworkArray() {
    // setlocale(LC_ALL, 'de_DE@euro');
    // $this->tools->monitor(localeconv());
    $aMasterStruktur = $this->struktur->getMasterStruktur();
    // $this->tools->monitor($aMasterStruktur);
    // $this->tools->monitor($this->aAllProducts);
    
    $this->aArticleStatusFlat = $this->getArticleStatus();
    // $this->tools->monitor($this->aArticleStatusFlat);
    
    foreach ($this->aAllProducts as $sArtIndex => $aArtikel) {
      $parent_child = $aArtikel['parent-child'];
      // $this->tools->monitor($aArtikel);
      foreach ($aArtikel as $sFieldName => $sFieldValue) {
        // $this->tools->monitor($aMasterStruktur['Fields'][$sFieldName]['StripHTMLTags']);
        // $this->tools->monitor($sFieldName, true, '1 - $sFieldName');
        // $this->tools->monitor($this->aAllProducts[$sArtIndex][$sFieldName], true, '2 - $this->aAllProducts[$sArtIndex][$sFieldName]');
        // $this->tools->monitor($aMasterStruktur['Fields'][$sFieldName]['StripHTMLTags'], true, '3 - $aMasterStruktur[Fields][$sFieldName][StripHTMLTags]');
        if ($aMasterStruktur['Fields'][$sFieldName]['StripHTMLTags'] == "true" AND $this->aAllProducts[$sArtIndex][$sFieldName] != '') {
          $S1 = $this->aAllProducts[$sArtIndex][$sFieldName];
          // $this->tools->monitor($S1, true, '5');
          $S2 = html_entity_decode($S1, ENT_COMPAT, 'UTF-8');
          // $this->tools->monitor($S2, true, '6');
          $S3 = strip_tags($S2);
          // $this->tools->monitor($S3, true, '7');
          $this->aAllProducts[$sArtIndex][$sFieldName] = $S3;
          // $this->tools->monitor($this->aAllProducts[$sArtIndex][$sFieldName], true, '8 - $this->aAllProducts[$sArtIndex][$sFieldName]');
          unset($S1, $S2, $S3);
        }
        if ($aMasterStruktur['Fields'][$sFieldName]['Prefix'.$parent_child] != '' AND $this->aAllProducts[$sArtIndex][$sFieldName] != '') {
          $this->aAllProducts[$sArtIndex][$sFieldName] = $aMasterStruktur['Fields'][$sFieldName]['Prefix'.$parent_child] . $this->aAllProducts[$sArtIndex][$sFieldName];
        }
        if ($aMasterStruktur['Fields'][$sFieldName]['Format'] != '' AND $this->aAllProducts[$sArtIndex][$sFieldName] != '') {
          $this->aAllProducts[$sArtIndex][$sFieldName] = sprintf('%01.2f', $this->aAllProducts[$sArtIndex][$sFieldName]);
        }
        // $this->tools->monitor($parent_child);
        if ($aMasterStruktur['Fields'][$sFieldName]['Mandatory'.$parent_child] != '' AND $this->aAllProducts[$sArtIndex][$sFieldName] != '') {
          if ($aMasterStruktur['Fields'][$sFieldName]['Mandatory'.$parent_child] == 'Clear') {
            $this->aAllProducts[$sArtIndex][$sFieldName] = "";
          } else {
            $this->aAllProducts[$sArtIndex][$sFieldName] = $aMasterStruktur['Fields'][$sFieldName]['Mandatory'.$parent_child];
          }
        }
        if ($aMasterStruktur['Fields'][$sFieldName]['MaxChar'] != '' AND $aMasterStruktur['Fields'][$sFieldName]['MaxChar'] != NULL AND $aMasterStruktur['Fields'][$sFieldName]['MaxChar'] != 0 AND $this->aAllProducts[$sArtIndex][$sFieldName] != '') {
          $this->aAllProducts[$sArtIndex][$sFieldName] = $this->tools->truncate($this->aAllProducts[$sArtIndex][$sFieldName], $aMasterStruktur['Fields'][$sFieldName]['MaxChar'], '', false);
        }
        // Setzt alle Einträge in Feldern, deren Feldnamen image enthalten und Feldinhalte nopig.jpg auf leer.
        // Es werden somit alle nopic.jpg (kein Bild vorhanden) Einträge gelöscht
        if (strstr($sFieldName, 'image') != false AND strstr($this->aAllProducts[$sArtIndex][$sFieldName], 'nopic.jpg') != false){
          $this->aAllProducts[$sArtIndex][$sFieldName] = '';
        }
      }
      
      // $this->tools->monitor($this->aArticleStatusFlat[$aArtikel['sku']]);
      if ( $this->aArticleStatusFlat[$aArtikel['sku']]['ALIAS'] != '' AND $this->aArticleStatusFlat[$aArtikel['sku']]['ALIAS'] != Null ) {
        $this->aAllProducts[$sArtIndex]['sku'] = $this->aArticleStatusFlat[$aArtikel['sku']]['ALIAS'];
      }
      if ( $this->aArticleStatusFlat[$aArtikel['sku']]['TEXTTRANSFER'] == 0 ) {
        $this->aAllProducts[$sArtIndex]['product-description'] = '';
      }
      if ( $this->aArticleStatusFlat[$aArtikel['sku']]['BILDTRANSFER'] == 0 ) {
        $this->aAllProducts[$sArtIndex]['main-image-url'] = '';
        $this->aAllProducts[$sArtIndex]['swatch-image-url'] = '';
        $this->aAllProducts[$sArtIndex]['other-image-url1'] = '';
        $this->aAllProducts[$sArtIndex]['other-image-url2'] = '';
        $this->aAllProducts[$sArtIndex]['other-image-url3'] = '';
        $this->aAllProducts[$sArtIndex]['other-image-url4'] = '';
        $this->aAllProducts[$sArtIndex]['other-image-url5'] = '';
        $this->aAllProducts[$sArtIndex]['other-image-url6'] = '';
        $this->aAllProducts[$sArtIndex]['other-image-url7'] = '';
        $this->aAllProducts[$sArtIndex]['other-image-url8'] = '';
      }     
      if ( $this->aArticleStatusFlat[$aArtikel['sku']]['BUILDNAME'] == 1 AND $this->aAllProducts[$sArtIndex]['parent-child'] == 'Child' ) {
        $this->aAllProducts[$sArtIndex]['product-name'] = $this->aAllProducts[$sArtIndex]['product-name'] . ', ' . $this->aAllProducts[$sArtIndex]['color'].' '.$this->aAllProducts[$sArtIndex]['size'];
      }
    }
    // $this->tools->monitor($this->aAllProducts);
  }
  
  public function convertOutput() {
    //$this->CsvOutput = utf8_encode($this->CsvOutput);
    $GivenCharset    = mb_detect_encoding ( $this->CsvOutput, "auto");
    $this->CsvOutput = mb_convert_encoding( $this->CsvOutput, $this->aChannelSettings['CharSet'], $GivenCharset);
  }
  
  public function getHtmlTree($aArticles, $CssId) {
    $Einrueck = 2;
    // $this->tools->monitor(current($aArticles));
    if ( empty($aArticles) ) return;
    $aLevel = current($aArticles);
    if ( $aLevel['Level'] == 1 ) {
      echo str_repeat(' ', $Einrueck * $aLevel['Level']).'<ul id="'.$CssId.'">'."\n";
    } else {
      echo str_repeat(' ', $Einrueck * $aLevel['Level']).'<ul>'."\n";
    }
    unset($aLevel);
    reset($aArticles);
    foreach ( $aArticles AS $Key => $Value ) {
      echo str_repeat(' ', $Einrueck * $Value['Level'] + 2).'<li class="STATUS-'.$Value['STATUS'].' Level-'.$Value['Level'].'">'."\n";
      echo str_repeat(' ', $Einrueck * $Value['Level'] + 4).'<a href="?Job=Edit&ArtKey='.$Key.'&Level='.$Value['Level'].'" class="STATUS-'.$Value['STATUS'].' Level-'.$Value['Level'];
      settype($Key, "string");
      if ( $Key === $_REQUEST['ArtKey'] ) echo ' Aktiv';
      // echo " data-key={$Key} data-artkey={$_REQUEST['ArtKey']}";
      echo '">'.$Key.'</a>'."\n";
        if ( !empty($Value['Child']) ) {
          // echo '<ul>'."\n";
            $this->getHtmlTree($Value['Child'], $CssId);
          // echo '</ul>'."\n";
        }
      echo str_repeat(' ', $Einrueck * $Value['Level'] + 2).'</li>'."\n";
    }
    echo @str_repeat(' ', $Einrueck * $aLevel['Level']).'</ul>'."\n";
  }
  
  public function editAttribute($aValues) {
    // $this->tools->monitor($aValues, true, 'editAttribute');
    $sSql = '';
    if ( !empty($aValues) AND is_array($aValues) ) {
      switch ( $aValues['Subjob'] ) {
        case 'Einfuegen':
          $sSqlRaw = "INSERT INTO `%s` ( `ChannelID`, `OXSHOPID`, `OXLOCATION`, `OXOBJECTID`, `TYPE`, `VALUE` ) VALUES ( '%s', '%s', '%s', '%s', '%s', '%s' );";
          $sSql = sprintf($sSqlRaw, $this->Tbl_Article_Attributes, $this->getChannelID(), $aValues['OXSHOPID'], $aValues['OXLOCATION'], $aValues['OXID'], $aValues['Key'], $aValues['Value']);
          break;
        case 'Loeschen':
          $sSqlRaw = "DELETE FROM `%s` WHERE `ChannelID` = '%s' AND `OXSHOPID` = '%s' AND `OXLOCATION` = '%s' AND `OXOBJECTID` = '%s' AND  `TYPE` = '%s';";
          $sSql = sprintf($sSqlRaw, $this->Tbl_Article_Attributes, $this->getChannelID(), $aValues['OXSHOPID'], $aValues['OXLOCATION'], $aValues['OXID'], $aValues['TYPE']);
          break;
        case 'Speichern':
          $sSqlRaw = "UPDATE `%s` SET `VALUE` = '%s' WHERE `ChannelID` = '%s' AND `OXSHOPID` = '%s' AND `OXLOCATION` = '%s' AND `OXOBJECTID` = '%s' AND `TYPE` = '%s';";
          $sSql = sprintf($sSqlRaw, $this->Tbl_Article_Attributes, $this->getChannelID(), $aValues['ValueNew'], $aValues['OXSHOPID'], $aValues['OXLOCATION'], $aValues['OXID'], $aValues['TYPE']);
          break;
        default:
          echo 'Autsch';
      }
    }
    
    if ( !empty($sSql) ) {
      // $this->tools->monitor($sSql);
      $this->DB->query($sSql);
      $this->DeleteTmp->clearCache('CACHE');
    }
  }
  
  public function editStatus($aValues) {
    $sSql = '';
    if ( !empty($aValues) AND is_array($aValues) ) {
      // $this->tools->monitor($aValues);
      switch ( $aValues['Subjob'] ) {
        case 'StatusSave':
          $sSqlRaw = "UPDATE `%s` SET `STATUS` = '%s', `COPYFROM` = '%s', `ALIAS` = '%s', `BILDTRANSFER` = '%s', `TEXTTRANSFER` = '%s', `BUILDNAME` = '%s'  WHERE `ChannelID` = '%s' AND `OXID` LIKE '%s';";
          if ($aValues['Vererbung'] == 'true') $aValues['OrgOXID'] = $aValues['OrgOXID'] . '%';
          $sSql = sprintf($sSqlRaw, $this->Tbl_Article_Status, $aValues['STATUS'], $aValues['COPYFROM'], $aValues['ALIAS'], $aValues['BILDTRANSFER'], $aValues['TEXTTRANSFER'], $aValues['BUILDNAME'], $this->getChannelID(), $aValues['OrgOXID']);
          break;
        case 'StatusInsertSave':
          $sSqlRaw = "INSERT INTO `%s` ( `ChannelID`, `OXID`, `PARENTID`, `STATUS`, `COPYFROM`, `ALIAS`, `BILDTRANSFER`, `TEXTTRANSFER`) VALUES ( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' );";
          $sSql = sprintf($sSqlRaw, $this->Tbl_Article_Status, $this->getChannelID(), $aValues['OXID'], $aValues['PARENTID'], $aValues['STATUS'], $aValues['COPYFROM'], $aValues['ALIAS'], $aValues['BILDTRANSFER'], $aValues['TEXTTRANSFER']);
          break;
        case 'StatusInsertSaveFormOxid':
          // $this->tools->monitor($this->getArticleIdsFromOxid($aValues['PARENTID']));
          foreach ( $this->getArticleIdsFromOxid($aValues['PARENTID']) AS $ArticleGr ) {
            $sSqlRaw = "INSERT INTO `%s` ( `ChannelID`, `OXID`, `PARENTID`, `STATUS`, `BILDTRANSFER`, `TEXTTRANSFER`) VALUES ( '%s', '%s', '%s', '%s', '%s', '%s' );\n";
            $sSql = sprintf($sSqlRaw, $this->Tbl_Article_Status, $this->getChannelID(), $ArticleGr['OXID'], $ArticleGr['OXPARENTID'], $aValues['STATUS'], $aValues['BILDTRANSFER'], $aValues['TEXTTRANSFER']);
            
            if ( !empty($sSql) ) $this->DB->query($sSql);
            $sSql = '';
          };
          break;
        default:
          echo 'Autsch';
      }
    }
    // $this->tools->monitor($sSql);
    if ( !empty($sSql) ) {
      // $this->tools->monitor($sSql);
      $this->DB->query($sSql);
    }
    $this->DeleteTmp->clearCache('CACHE');
  }
    
  public function ConvertASCIIs($sVar) {
    if ( empty($sVar) ) return ""; 
    $aVar = explode(",", $sVar);
    // $this->tools->monitor($aVar, true);
    $output = "";
    foreach ($aVar as $singleVar) {
      $output .= chr($singleVar);
    }
    return $output;
  }
  

  
}

