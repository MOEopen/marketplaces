    <?php
      function getJob() {
        if ( !empty ($_REQUEST['Job']) ) {
          return strtolower($_REQUEST['Job']);
        } else {
          return "";
        }
      } 
      
      $ChannelId = 1;
      
      $Classname = getJob().".inc.php";
      if ( file_exists( $Classname ) ) {
        require_once( $Classname );
        $sJob = getJob();
        $oJob = new $sJob;
        $oJob->run();
      } else {
        echo "Die gewählte Funktion existiert nicht.";
      }
      
      exit();
      
      
      require_once('Marketplaces.class.php');
      $Marketplaces = new tru_Marketplaces();
      // $Marketplaces->tools->monitor($_SERVER);
      
      if( !empty($_REQUEST['Job'] )) {
        $sJob = $_REQUEST['Job'];
      } else {
        $sJob = '';
      }
      switch ($sJob) {
        case 'Export':
          $Marketplaces->Export();

          // $Marketplaces->tools->monitor($Marketplaces->struktur->getMasterStruktur());
          // $Marketplaces->tools->monitor($Marketplaces->aAllProducts);
          // echo "<pre>";
          // echo $Marketplaces->CsvOutput;
          // echo "</pre>";
          if (!empty($Marketplaces->Filename)) {
            echo "<a href=\"{$Marketplaces->Filename}\">Download</a>";
          }
          break;
        case 'Edit':
          require_once('Marketplaces.Formular.Tabs.php');
          break;
        case 'Status':
          
          break;
        case 'DelCache':
          $Marketplaces->DeleteTmp->printOptions();
          if ( !empty($_GET['w']) ) echo $Marketplaces->DeleteTmp->clearCache($_GET['w']);
          // require_once('deleteTmp.php');
          break;
        case 'TransDb':
          require_once('Marketplaces.TransferDb.php');
          $import = new tru_Import;
          $import->run();
          echo "Transfer Ende";
          break;
      }
      
    ?>