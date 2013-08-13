<?php
class export {
  
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

    require_once('objects/channel.php');
    $this->Channel = new Channel();

  }
  
  public function run() {
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
  
  
  
  
  
  
}



?>