<?php

use eftec\ValidationOne;
use mapache_commons\Collection;

include __DIR__."/../app_start/App.php";
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
$r = getVal()->default('ERROR')
    ->type('integer')
    ->friendId('Id of example')
    ->condition("eq", "It's not equals to 10 (error) [%field] [%realfield] [%value] [%comp]", 10)
    ->condition("eq", "It's not equals to 30 (info)", 30, 'info')
    ->ifFailThenDefault()
    ->set([1,'aaa','bbbb'], 'id',"some error message");


$r2 = getVal()->default('ERROR')
    ->type('integer')
    ->ifFailThenDefault()
    ->required()->get('id',"Missing id field from get");


$timeEnd = microtime(true);
echo json_encode(getVal()->errorList->items,JSON_PRETTY_PRINT);
echo "<hr>";
echo Collection::generateTable(getErrorList()->allArray());
echo Collection::generateTable(getErrorList()->get('id[2]')->allErrorOrWarning());
echo (getErrorList()->get('id[2]')->firstError())."<br>";

