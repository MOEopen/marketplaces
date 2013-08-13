<?php
class export {
  
  function __construct() {
    $reg = Registry::getInstance();
    
    require_once('struktur_def.class.php');
    $reg->set('struktur_def', new tru_struktur_def());
  }
  
  public function run() {
    // $this->getAktiveArticle();
    // $reg->get('Debug')->monitor($this->aAktiveArticles);
    
    $this->enrichArticle();
    // $reg->get('Debug')->monitor($this->aAllProducts);
    
    // $this->BuildParent();
    // $reg->get('Debug')->monitor($this->aAllProducts);
    // $this->BuildChild();
    // $reg->get('Debug')->monitor($this->aAllProducts);

    $this->SortArray();
    // $reg->get('Debug')->monitor($this->aAllProducts);
    
    $this->ReworkArray();
    // $reg->get('Debug')->monitor($this->aAllProducts);

    $this->Convert2Csv();
    $this->Save2File();
  }
  
  public function enrichArticle() {
    // Sicherstellen, dass die MasterStruktur geladen ist.
    if ( empty($reg->get('struktur_def')->aMasterStruktur) ) $reg->get('struktur_def')->getMasterStruktur();
    // $reg->get('Debug')->monitor($reg->get('struktur_def')->aMasterStruktur);
    
    // Sicherstellen, das die aktiven Artikel geladen sind
    if ( empty($this->aAktiveArticles) ) $this->getAktiveArticle();
    // $reg->get('Debug')->monitor($this->aAktiveArticles);
    
    // Sicherstellen, dass die Attribute geladen sind
    if ( empty($this->aAttributes) ) $this->getAttributes();
    // $reg->get('Debug')->monitor($this->aAttributes);
    
    foreach ($this->aAktiveArticles as $OXID_Mod => $aArticle_Mod) {
      // Daten von oxarticle an Rohstruktur anfügen
      $aExportMod = array_merge($reg->get('struktur_def')->getRawStruktur('Parent'), $this->getArticleFromOxid($aArticle_Mod['OXID'], $aArticle_Mod['COPYFROM'], 'Parent'));
      // $reg->get('Debug')->monitor($aExportMod);
      
      // Attribute überschreiben
      if ( @is_array($this->aAttributes[$OXID_Mod]) ) {
        $aExportMod = @array_merge($aExportMod, $this->aAttributes[$OXID_Mod]);
      }
      // $reg->get('Debug')->monitor($aExportMod);
      
      // An Ausgabe Array anfügen
      $this->aAllProducts[$OXID_Mod] = $aExportMod;
      
      // Zwischenaufbau der Artikelebene
      foreach ( $aArticle_Mod['Child'] as $OXID_Art => $aArticle_Art) {
        // Daten von oxarticle an Rohstruktur anfügen
        $aExportArticle = $aArtOxid = array_merge($reg->get('struktur_def')->getRawStruktur('Parent'), $this->getArticleFromOxid($aArticle_Art['OXID'], $aArticle_Art['COPYFROM'], 'Parent'));
        // $reg->get('Debug')->monitor($aExportArticle);
        
        // Attribute von Übergeordnetem Modell anfügen
        if ( @is_array($this->aAttributes[$OXID_Mod]) ) {
          $aExportArticle = @array_merge($aExportArticle, $this->aAttributes[$OXID_Mod]);
        }
        // $reg->get('Debug')->monitor($aExportArticle);
        
        // Attribute von Artikel anfügen
        if ( @is_array($this->aAttributes[$OXID_Art]) ) {
          $aExportArticle = @array_merge($aExportArticle, $this->aAttributes[$OXID_Art]);
        }
        // $reg->get('Debug')->monitor($aExportArticle);
        
        // Aufbau der Größenebene
        foreach ($aArticle_Art['Child'] AS $OXID_Groesse => $aArticle_Groesse) {
          
          // Daten von oxarticle an Rohstruktur anfügen
          $aExportGroesse = array_merge($reg->get('struktur_def')->getRawStruktur('Child'), $this->getArticleFromOxid($aArticle_Groesse['OXID'], $aArticle_Groesse['COPYFROM'], 'Child'));
          $aExportGroesse['parent-sku'] = $OXID_Mod;
          
          // #######################
          // Überschreibe $aChildArticle mit Werten des Parent falls Feld gefüllt
          foreach ($aArtOxid AS $ParentKeys => $ParentValue) {
            if( !empty($ParentValue) AND $reg->get('struktur_def')->aMasterStruktur['Fields'][$ParentKeys]['inheritable'] == true ) $aExportGroesse[$ParentKeys] = $ParentValue;
          }
          // $reg->get('Debug')->monitor($aExportGroesse); 
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
          // $reg->get('Debug')->monitor($aExportGroesse);
          
          // An Ausgabe Array anfügen
          $this->aAllProducts[$OXID_Groesse] = $aExportGroesse;
        }
        
      }
    }
  }
  
  
  
  
  
  
}



?>