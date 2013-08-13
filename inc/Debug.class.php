<?php
class Debug {
  public function monitor($obj, $print = true) {
    ob_start();
    echo "\n#########################\n<pre>\n";
    print_r($obj);
    echo "\n</pre>\n";
    $out = ob_get_contents();
    ob_end_clean();
    if ($print == true) echo $out;
    return $out;
  }
}