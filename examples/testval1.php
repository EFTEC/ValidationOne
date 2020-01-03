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

echo "<h1>Testing to fetch value from get (test 2)</h1>";
echo "<a href='?frm_id=12345'>click here for test a value</a><br><br>";

$example=new Example();

function getExample() {
    echo "injecting..";
    return new Example();

}

$x=new \somespace\Someclass();


//var_dump(call_user_func('customval', 'aaa','bbb'));


$r=getVal()->def(null)
    ->type('integer')
    ->condition('fn.static.Example.customval','la funcion no funciona',20) // this calls a custom function
    ->condition('req')
    ->condition('lt',"es muy grande",2000,'warning')
    ->condition('eq','%field %value is not equal to %comp ',50)->get("id");


$r=getVal()->def('ERROR')
    ->type('integer')
    ->condition('fn.static.Example.customval','la funcion no funciona',20)
    ->condition('req')
    ->condition('lt',"es muy grande",2000,'warning')
    ->condition('eq','%field %value is not equal to %comp',50)
    ->condition('fn.static.Example.fnstatic','la funcion estatica no funciona')
    ->condition('fn.static.\somespace\Someclass.methodStatic',null)
    ->condition('fn.global.customval','la funcion global no funciona')
    ->condition('fn.object.example.fnnostatic','la funcion object no funciona')
    ->condition('fn.class.\somespace\Someclass.method','la funcion someclass no funciona')
    ->condition('fn.class.Example.fnnostatic','la funcion class no funciona')
    ->ifFailThenDefault()
    ->get("id");

echo "The value is ".print_r($r,true)."<br>";

dump(getVal()->messageList->get('id')->allErrorOrWarning());

dump(getVal()->messageList->get("id"));
echo "<hr>with id2: (there is not a container with id2)<br>";

dump(getVal()->messageList->get('id2')->allErrorOrWarning());

dump(getVal()->messageList->get("id2"));
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
    $r = getVal()->def('ERROR')
        ->type('integer')
        //->reset()
        ->condition("eq", "It's not equals to 10", 10)
        ->condition("eq", "It's not equals to 30 (info)", 30, 'info')
        ->ifFailThenDefault()
        ->set('aaaa', 'id');
}

$memoryEnd = memory_get_usage();
echo $memoryEnd-$memoryStart."<br>";
dump(getVal()->messageList);

$timeEnd = microtime(true);
dump(getVal()->messageList->allArray());

echo "<br>time:".($timeEnd-$timeStart)."<br>";
