<?php

use eftec\ValidationOne;


include "common.php";

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


    //getVal()->reset();
$r = getVal()->def(['ERROR'])
    ->type('integer')->ifFailThenDefault()
    ->isArray(true)->get('id',"some error message on %field");


var_dump($r);

$timeEnd = microtime(true);
echo json_encode(getVal()->messageList->items,JSON_PRETTY_PRINT);
echo "<hr>";
echo json_encode(getMessageList()->allArray(),JSON_PRETTY_PRINT);
echo json_encode(getMessageList()->get('id[2]')->allErrorOrWarning(),JSON_PRETTY_PRINT);
echo (getMessageList()->get('id[2]')->firstError())."<br>";

