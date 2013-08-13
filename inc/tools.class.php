<?php
class tru_tools {
  
  public function monitor($obj, $print = true, $Komentar = "")
  {
    ob_start();
    echo "\n#########################";
    if ($Komentar != "") echo "<br />\n".$Komentar;
    echo "\n<pre>\n";
    print_r($obj);
    echo "\n</pre>\n";
    $out = ob_get_contents();
    ob_end_clean();
    if ($print == true) echo $out;
    return $out;
  }

  /**
  * Generatting CSV formatted string from an array.
  * By Sergey Gurevich.
  */
  public function array_to_csv($array, $header_count = 1, $col_sep = ",", $row_sep = "\n", $qut = '"') {
    if (!is_array($array) ) return false;
    
    $header = "";
    $output = "";
    //Header row.
    if ($header_count > 0)
    {
      foreach ($array as $key => $val)
      {
        //Escaping quotes.
        $key = str_replace($qut, "$qut$qut", $val);
        foreach($val as $key2 => $val2) {
          $fields[] = $key2;
        }
        $key3 = implode($qut.$col_sep.$qut, $fields);
        $header = "$col_sep$qut$key3$qut";
        break;
      }
      
      $header = substr($header, 1)."\n";
      // $header .= $header;
      
      for ($i = 1; $i <=$header_count ; $i++) {
        $output .= $header;
      }
    }

    //Data rows.
    foreach ($array as $key => $val)
    {
      $tmp = '';
      foreach ($val as $cell_key => $cell_val)
      {
        //Escaping quotes.
        $cell_val = str_replace($qut, "$qut$qut", $cell_val);
        $tmp .= "$col_sep$qut$cell_val$qut";
      }
      $output .= substr($tmp, 1).$row_sep;
    }
    
    return $output;
  }
  
  /*
  * $string      - zu kürzende Zeichenkette
  * $length      - Max. Anzahl zurückzugebender Zeichen
  * $break_words - true/false ob Wörter zerschnitten werden sollen
  * $middle      - true/false ob Anfang und Ende gegeben werden sollen, besser false benutzen
  */
  function truncate($string, $length = 80, $etc = '...', $break_words = false, $middle = false) {
    if ($length == 0) return '';

    if (strlen($string) > $length) {
      $length -= min($length, strlen($etc));
      if (!$break_words && !$middle) {
        $string = preg_replace('/\s+?(\S+)?$/', '', substr($string, 0, $length+1));
      }
      if(!$middle) {
        return substr($string, 0, $length) . $etc;
      } else {
        return substr($string, 0, $length/2) . $etc . substr($string, -$length/2);
      }
    } else {
      return $string;
    }
  }
  
  public function Save2File($Filename, $Filedata) {
    $FilePointer = fopen($Filename, 'a+');
    // Kodierung UTF8 mit Bom setzen
    // fwrite ($FilePointer, pack("CCC",0xef,0xbb,0xbf));
    fwrite ($FilePointer, "###################################\n");
    fwrite ($FilePointer, strftime("%Y-%m-%d_%H-%M-%S", time())."\n");
    fwrite ($FilePointer, $Filedata);
    fwrite ($FilePointer, "\n");
    fclose($FilePointer);
  }

}