#!/usr/bin/env php
<?php
/* Set internal character encoding to UTF-8 */
mb_internal_encoding("UTF-8");

class HP_Options{

  var $short_names = array(
    'f:', // log file name
    'd:', // database file name
  );
  
  var $long_names = array(
    'single:'
  );
  
  //$options holds the default values
  var $options = array(
    'f' => 'log.yaml',
    'd' => 'food.yaml',
  );
  
  public function __construct($argv){
    $this->options = array_merge(getopt(implode($this->short_names), $this->long_names), $this->options);
  }
  
  function get($name, $default = FALSE){
    if (isset($this->options[$name])){
      if ($this->options[$name]){
        return $this->options[$name];
      } else {
        return TRUE;
      }
    }
    return $default;
  }
}

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

class HP_Accumulator{
  
  public $list = array();
  
  public function add($name, $value){
    $sign = ($value<0)?'-':'+';
    if (isset($this->list[$name][$sign])){
      $this->list[$name][$sign] += $value;
    } else {
      $this->list[$name][$sign] = $value;
    }
  }
  
  public function clear(){
    $this->list = array();
  }
}

class HR_Resolver{

  private $db;
  
  public static function sum_merge($a1, $a2, $coef = 1){
    $o = array();
    foreach($a1 as $k => $v){
      if (isset($o[$k])){
        $o[$k] += $v * $coef;
      } else {
        $o[$k] = $v * $coef;
      }
    }
    foreach($a2 as $k => $v){
      if (isset($o[$k])){
        $o[$k] += $v * 1;
      } else {
        $o[$k] = $v * 1;
      }
    }
    return $o;
  }

  private function resolve_node($name, $level){
    if ($level > 10){
      return;
    }
    if (!isset($this->db[$name])){
      return;
    }
    $tc = array();
    foreach($this->db[$name] as $n => $v){
      $this->resolve_node($n, $level+1);
      if (isset($this->db[$n])){
        $tc = $this->sum_merge($this->db[$n], $tc, $v);
      } else {
        $tc = $this->sum_merge($tc, array($n => $v));
      }
  
    }
    $this->db[$name] = $tc;
  }

  public function resolve($db){
    $this->db = $db;
    foreach($this->db as $name => $cont){
      $this->resolve_node($name, 0);
    }
    return $this->db;
  }
}

class HP_Output{
  
  /*
   * mb_str_pad from http://php.net/manual/en/ref.mbstring.php#90611
   */
  private static function mb_str_pad($input, $pad_length, $pad_string, $pad_style = STR_PAD_RIGHT) { 
    return str_pad($input, strlen($input)-mb_strlen($input)+$pad_length, $pad_string, $pad_style); 
  } 
    
  private static function printDate($date, $nl = "\n"){
    print date('Y/d/m', $date).":".$nl;
  }
  
  private static function printTrack($track){
    print "\t--".self::mb_str_pad($track, 69, '-')."\n";
  }
  
  private static function printElement($element, $pvalues){
    $el_name = self::mb_str_pad($element, 37, ' ', STR_PAD_LEFT);
    printf(" %s | %10.2f | %10.2f |=%10.2f |\n", $el_name, $pvalues['+'], $pvalues['-'], $pvalues['=']);
  }
  
  private static function getValues($values){
    $p = (isset($values['+']))?$values['+']:0;
    $m = (isset($values['-']))?$values['-']:0;
    return array(
      '+' => $p,
      '-' => $m,
      '=' => $p+$m,
    );
  }
  
  public static function outputTable($log){
    $acc = new HP_Accumulator();
    foreach ($log as $date => $rows){
      self::printDate($date);
      foreach($rows as $track => $elements){
        self::printTrack($track);
        foreach($elements as $element => $values){
          $pvalues = self::getValues($values);
          self::printElement($element, $pvalues);
          $acc->add($element, $pvalues['=']);
        }
      }
      // Show accumulated values;
      if ($acc->list){
        self::printTrack('TOTAL');
        foreach($acc->list as $element => $values){
          $pvalues = self::getValues($values);
          self::printElement($element, $pvalues);
        }
        $acc->clear();
      }
    }
  }
  
  public static function outputSingle($log, $single){
    $value = array();
    foreach ($log as $date => $rows){
      foreach($rows as $track => $elements){
        foreach($elements as $element => $values){
          if ($element == $single){
            $pvalues = self::getValues($values);
            $value = HR_Resolver::sum_merge($value, $pvalues, 1);
          }
        }
      }
      if ($value){
        self::printDate($date, '');
        $pvalues = self::getValues($value);
        self::printElement($single, $pvalues);
        $value = array();
      }
    }
  }
}

class HP_Processor{
  
  const errorChar = '# ';
  
  private $db;
  
  public function __construct(&$db){
    $this->db = $db;
  }
  
  private function getDbRow($name){
    if (isset($this->db[$name])){
      return $this->db[$name];
    }
    return FALSE;
  }

  public function parseDate($date){
    $d = strtotime($date);
    if ($d > 0){
      return $d;
    } else {
      return self::errorChar.$date;
    }
  }
  
  private function parseQty($raw_qty){
    return floatval(trim($raw_qty));
  }  
  
  public function processNode($rows){
    $olog = array();
    foreach($rows as $name => $raw_qty){
      $db_row = $this->getDbRow(trim($name));
      if ($db_row){
        $qty = $this->parseQty($raw_qty);
        $coef = 1;
        $elements = array();
        foreach($db_row as $rname => $rqty){
          $q = $rqty * $qty * $coef;
          $sign = ($q<0)?'-':'+';
          $elements[$rname][$sign] = $q;
        }
        $olog[$name] = $elements;
      } else {
        $sign = ($raw_qty<0)?'-':'+';
        $olog[$name] = array($name => array($sign =>$raw_qty));
      }
    }
    return $olog;
  }
  
  public function processLog(&$log){
    $olog = array();
    foreach($log as $date => $rows){
      $ts = $this->parseDate($date);
      $olog[$ts] = $this->processNode($rows);
    }
    return $olog;
  }
}

class Hranoprovod{

  private $conf = null;
  private $db = array();
  private $log = array();
  
  function __construct($argv){
    $this->conf = new HP_Options($argv);
    $database_file = $this->conf->get('d');
    $this->loadDatabase($database_file);
    
    $log_file = $this->conf->get('f');
    $this->loadLog($log_file);
    $single = $this->conf->get('single');
    if ($single){
      HP_Output::outputSingle($this->log, $single);
      return;
    }
    HP_Output::outputTable($this->log);
  }

  private function loadDatabase($database_file){
    $raw_db = HP_Parser::LoadFile($database_file);
    $this->db = $this->processDatabase($raw_db);
    $resolver = new HR_Resolver();
    $this->db = $resolver->resolve($this->db);
    unset($resolver);
  }

  private function processDatabase($raw){
    return $raw;
  }

  /*
   * callback function for log processing
   */
  public function addToLog($name, $elements){
    $new_name = $this->processor->parseDate($name);
    $new_elements = $this->processor->processNode($elements);
    $this->log[$new_name] = $new_elements;
  }  
  
  
  public function loadLog($log_file){
    $this->processor = new HP_Processor($this->db);
    HP_Parser::LoadFile($log_file, array($this, 'addToLog'));
    unset($processor);
  }  
}

$h = new Hranoprovod($argv);