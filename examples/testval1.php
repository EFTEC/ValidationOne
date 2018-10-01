<?php

use eftec\ValidationOne;


include "common.php";
include "dBug.php";
function customval($value,$compareValue) {
    return true;
}
class Example {
    public static function fnstatic($value,$compareValue) {
        return true;
    }
    public function fnnostatic($value,$compareValue) {
        return false;
    }
}

$example=new Example();

function getExample() {
    echo "injecting..";
    return new Example();

}

//$x=new \somespace\Someclass();


//var_dump(call_user_func('customval', 'aaa','bbb'));
/*

$r=getVal()->default(null)
    ->type('integer')
    ->condition('fn.static.Example.customval','la funcion no funciona',20)
    ->condition('req')
    ->condition('lt',"es muy grande",2000,'warning')
    ->condition('eq','%s [%2$s] is not equal to %3$s ',50)->get("id");

 */
/*
$r=getVal()->default('ERROR')
    ->type('integer')
    ->condition('fn.static.Example.customval','la funcion no funciona',20)
    ->condition('req')
    ->condition('lt',"es muy grande",2000,'warning')
    ->condition('eq','%s [%2$s] is not equal to %3$s ',50)
    ->condition('fn.static.Example.fnstatic','la funcion estatica no funciona',20)
    ->condition('fn.static.\somespace\Someclass.methodStatic',null,20)
    ->condition('fn.global.customval','la funcion global no funciona',20)
    ->condition('fn.object.example.fnnostatic','la funcion object no funciona',20)
    ->condition('fn.class.\somespace\Someclass.method','la funcion someclass no funciona',20)
    ->condition('fn.class.Example.fnnostatic','la funcion class no funciona',20)
    ->ifFailThenDefault()
    ->get("id");

echo "el valor es ".print_r($r,true)."<br>";

echo Collection::generateTable(getVal()->errorDic->get('id')->all());
var_dump(getVal()->errorDic->msg("id"));
echo "<hr>con id2:<br>";
echo Collection::generateTable(getVal()->errorDic->get('id2')->all());

var_dump(getVal()->errorDic->msg("id"));
echo "<hr>";
*/

$timeStart = microtime(true);
$memoryStart = memory_get_usage();
for($i=0;$i<1000;$i++) {
    //getVal()->reset();
    $r = getVal()->default('ERROR')
        ->type('integer')
        //->reset()
        ->condition("eq", "It's not equals to 10", 10)
        ->condition("eq", "It's not equals to 30 (info)", 30, 'info')
        ->ifFailThenDefault()
        ->set('aaaa', 'id');
}

$memoryEnd = memory_get_usage();
echo $memoryEnd-$memoryStart."<br>";
echo json_encode(getVal()->errorList, JSON_PRETTY_PRINT);

$timeEnd = microtime(true);
echo json_encode(getVal()->errorList->allArray(),JSON_PRETTY_PRINT);

echo "<br>time:".($timeEnd-$timeStart)."<br>";
