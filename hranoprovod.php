<?php

require 'lib/spyc-0.5/spyc.php';

class Hranoprovod{
	
	private $db = array();
	private $log = array();

	function __construct($database){
		$this->loadDatabase($database);
  	}

	private function sum_merge($a1, $a2, $coef = 1){
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

	private function resolve(){
		foreach($this->db as $name => $cont){
			$this->resolve_node($name, 0);
		}
	}


	private function loadDatabase($database){
		$raw_db = Spyc::YAMLLoad($database);
		$this->db = $this->processDatabase($raw_db);
		$this->resolve();
	}

	private function processDatabase($raw){
		return $raw;
	}

	public function loadLog($log){
		$raw_log = Spyc::YAMLLoad($log);
		$log = $this->processLog($raw_log);
	}

	private function getDbRow($name){
		if (isset($this->db[$name])){
			return $this->db[$name];
		}
		return FALSE;
	}

	private function getCoef($coef, $name){
		if (isset($coef[$name])){
			return $coef[$name];
		}
		return FALSE;
	}

	private function processLog($log){
		$olog = array();
		foreach($log as $date => $rows){
			if ($date == '~'){
				//get daily
			}
			foreach($rows as $name => $raw_qty){
				$db_row = $this->getDbRow(trim($name));
				if ($db_row){
					list($qty, $measure) = $this->parseQty($raw_qty);
					$coef = 1;
					$elements = array();
					foreach($db_row as $rname => $rqty){
						if ($rname[0] != '!'){
							$elements[$rname] = $rqty * $qty * $coef;
						}
					}
					$olog[$date][][$name] = $elements;
				} else {
					$olog[$date][][$name] = array($name => $raw_qty);
				}
			}
		}
		$this->log = $olog;
		unset($olog);
	}

	private function parseQty($raw_qty){
		if (preg_match('/([\d\.\-\+]+)\s+(.+)/', $raw_qty, $m)){
			return array($m[1], $m[2]);
		}
		if (preg_match('/([\d\.\-\+]+)/', $raw_qty, $m)){
			return array($m[1], '');
		}
	}

	public function printOutput(){
		$acc = array();
		foreach ($this->log as $date => $rows){
			foreach ($rows as $elements){
				foreach ($elements as $name => $contents){
					if (!isset($acc[$date])) $acc[$date] = array();
					foreach ($contents as $ename => $eqty){
						if (!isset($acc[$date][$ename])) $acc[$date][$ename] = 0;
						if (is_numeric($eqty)){
							$acc[$date][$ename] += $eqty;
						}
					}
				}
			}
		}
		foreach($acc as $date => $elements){
			echo $date.":\n";
			foreach($elements as $name => $qty){
				echo "\t".$name.': '.round($qty, 2)."\n";
			}
		}
	}
}

$database = 'food.yaml';
$log = 'log.yaml';

$h = new Hranoprovod($database);
$h->loadLog($log);
$h->printOutput();


