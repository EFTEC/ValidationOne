<?php

use eftec\ValidationOne;


include "common.php";
include "Someclass.php";
function customval($value,$compareValue) {
    return ($value==$compareValue);
}
class Example {
    public static function fnstatic($value,$compareValue) {
        return ($value==$compareValue);
    }
    public function fnnostatic($value,$compareValue) {
        return ($value==$compareValue);
    }
}

$obj=new Example();


$r=getVal()->def(null)
    ->type('integer')
    ->condition('fn.static.Example.fnstatic','The custom function Example::fnstatic() expected a value of 20',20) // this calls a custom function
    ->condition('fn.global.customval','The custom function customval() expected a value of 20',20) // this calls a custom function
    ->condition('fn.object.obj.fnnostatic','The custom function $obj->fnnostatic() expected a value of 20',20) // this calls a custom function
    ->condition('req')
    ->condition('lt',"it is too big",2000,'warning')
    ->condition('eq','%field %value is not equal to %comp ',50)->get("id");

echo "<h1>Testing to fetch value from get</h1>";
echo "<a href='?id=12345'>click here for test a value</a><br><br>";

echo "The value of id is [<b>".print_r($r,true)."</b>]<br><br>";

echo "[id] Errors and warnings:";
dump(getVal()->messageList->get('id')->allErrorOrWarning());

echo "[id] container:";
dump(getVal()->messageList->get("id"));
echo "<hr>";
echo "[id2] Errors and warnings:";
dump(getVal()->messageList->get('id2')->allErrorOrWarning());
echo "[id2] container:";
dump(getVal()->messageList->get("id"));
echo "<hr>";



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

function dump($var) {
    echo "<pre>";
    echo json_encode($var,JSON_PRETTY_PRINT);
    echo "</pre>";

}