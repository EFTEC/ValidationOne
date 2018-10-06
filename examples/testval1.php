<?php

use eftec\ValidationOne;


include "common.php";
include "Someclass.php";
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

$x=new \somespace\Someclass();


//var_dump(call_user_func('customval', 'aaa','bbb'));


$r=getVal()->default(null)
    ->type('integer')
    ->condition('fn.static.Example.customval','la funcion no funciona',20) // this calls a custom function
    ->condition('req')
    ->condition('lt',"es muy grande",2000,'warning')
    ->condition('eq','%field %value is not equal to %comp ',50)->get("id");


$r=getVal()->default('ERROR')
    ->type('integer')
    ->condition('fn.static.Example.customval','la funcion no funciona',20)
    ->condition('req')
    ->condition('lt',"es muy grande",2000,'warning')
    ->condition('eq','%field %value is not equal to %comp',50)
    ->condition('fn.static.Example.fnstatic','la funcion estatica no funciona',20)
    ->condition('fn.static.\somespace\Someclass.methodStatic',null,20)
    ->condition('fn.global.customval','la funcion global no funciona',20)
    ->condition('fn.object.example.fnnostatic','la funcion object no funciona',20)
    ->condition('fn.class.\somespace\Someclass.method','la funcion someclass no funciona',20)
    ->condition('fn.class.Example.fnnostatic','la funcion class no funciona',20)
    ->ifFailThenDefault()
    ->get("id");

echo "el valor es ".print_r($r,true)."<br>";

dump(getVal()->errorList->get('id')->allErrorOrWarning());

dump(getVal()->errorList->get("id"));
echo "<hr>con id2:<br>";

dump(getVal()->errorList->get('id2')->allErrorOrWarning());

dump(getVal()->errorList->get("id"));
echo "<hr>";

function dump($var) {
    echo "<pre>";
    echo json_encode($var,JSON_PRETTY_PRINT);
    echo "</pre>";

}

$timeStart = microtime(true);
$memoryStart = memory_get_usage();
for($i=0;$i<10;$i++) {
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
dump(getVal()->errorList);

$timeEnd = microtime(true);
dump(getVal()->errorList->allArray());

echo "<br>time:".($timeEnd-$timeStart)."<br>";
