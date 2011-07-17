<?php

$a = array(
	'шоколад' => array(
		'мармалад' => 10,
		'хляб' => 20,
		'!m' => array(
			'бр' => 0.4,
			'парче' => 0.3,
		)
	),
	'кифла' => array(
		'c1' => 10,
		'c2' => 20,
	),
	'мармалад' => array(
		'кифла' => '10'
	),

);

//print_r($a);

function sum_merge($a1, $a2, $coef = 1){
	$o = array();
	foreach($a1 as $k => $v){
		if (is_numeric($v)){
			if (isset($o[$k])){
				$o[$k] += $v * $coef;
			} else {
				$o[$k] = $v * $coef;
			}
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

function resolve_node(&$arr, $name, $level){
	if ($level > 10){
		return;
	}
	if (!isset($arr[$name])){
		return;
	}
	$tc = array();
	foreach($arr[$name] as $n => $v){
		if ($n[0] == '!'){
			$tc[$n] = $v;
		} else {
echo $name.'=>'.$n.'=>'.$level."\n";
		resolve_node(&$arr, $n, $level+1);
			if (isset($arr[$n])){
				$tc = sum_merge($arr[$n], $tc, $v);
			} else {
				$tc = sum_merge($tc, array($n => $v));
			}
		}
	
	}
	$arr[$name] = $tc;
}

function resolve(&$arr){
	foreach($arr as $name => $cont){
		resolve_node($arr, $name, 0);
	}
}

resolve($a);
print_r($a);
