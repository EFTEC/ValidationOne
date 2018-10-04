<?php

use eftec\ValidationOne;
use mapache_commons\Collection;

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


    //getVal()->reset();
$r = getVal()->default(['ERROR'])
    ->type('integer')->ifFailThenDefault()
    ->isArray(true)->get('id',"some error message on %field");


var_dump($r);

$timeEnd = microtime(true);
echo json_encode(getVal()->errorList->items,JSON_PRETTY_PRINT);
echo "<hr>";
echo json_encode(getErrorList()->allArray(),JSON_PRETTY_PRINT);
echo json_encode(getErrorList()->get('id[2]')->allErrorOrWarning(),JSON_PRETTY_PRINT);
echo (getErrorList()->get('id[2]')->firstError())."<br>";

