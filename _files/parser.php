<?php

$file = '../log.yaml';

class HP_Parser{

  const ending = ':';

  public static function Load($file_name){
    if (!file_exists($file_name)){
      die('File not found');
    }
    $f = fopen($file_name, 'r');
    $result = array();
    $parent = FALSE;
    $elements = array();
    while (!feof($f)){
      $line = rtrim(fgets($f));
      if ($line){
        if (in_array($line[0], array(' ', "\t"))){
          if ($parent){
            $pos = max(strrpos($line, ' '), strpos($line, "\t"));
            if (!$pos){
              die('Wrong key: value format');
            }
            $k = rtrim(trim(substr($line, 0, $pos)), self::ending);
            $v = substr($line, $pos);
            if (isset($elements[$k])){
              $elements[$k] += $v;
            }
            $elements[$k] = $v;
          } else {
            die('Wrong format');
          }

        } else {
          if ($parent){
            //add to result
            $result[$parent] = $elements;
            //reset
            $parent = FALSE;
            $elements = array();
          }
          $parent = rtrim($line, self::ending);
        }
      }
    }
    if ($parent && $elements){
      $result[$parent] = $elements;
    }
    fclose($f);
    return $result;
  }

}

$arr = HP_Parser::Load($file);
print_r($arr);

?>
