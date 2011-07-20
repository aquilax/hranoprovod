<?php

$file = '../log.yaml';

class HP_Parser{

  const ending = ':';

  public static function LoadFile($file_name, $callback_function = FALSE){
    if (!file_exists($file_name)){
      die("File not found: $file_name\n");
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
            } else {
              $elements[$k] = $v;
            }
          } else {
            die('Wrong format');
          }
        } else {
          if ($parent){
            //add to result
            if (is_callable($callback_function)){
              call_user_func($callback_function, $parent, $elements);
            } else {
              $result[$parent] = $elements;
            }
            //reset
            $parent = FALSE;
            $elements = array();
          }
          $parent = rtrim($line, self::ending);
        }
      }
    }
    if ($parent && $elements){
      if (is_callable($callback_function)){
        call_user_func($callback_function, $parent, $elements);
      } else {
        $result[$parent] = $elements;
      }
    }
    fclose($f);
    return $result;
  }
}

class T{
  
  var $result = array();
  
  function p($name, $elements){
    $this->result[$name] = $elements;
  }
  
  function work($file){
    HP_Parser::LoadFile($file, array($this, 'p'));
    print_r($this->result);
  }
}

$t = new T();
$t->work($file);

//$arr = HP_Parser::LoadFile($file);
//print_r($arr);

?>
