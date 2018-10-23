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
$val=new ValidationOne();

    //$val->reset();
$r = $val->def('ERROR')
    ->type('integer')
    ->friendId('Id of example')
    ->condition("eq", "It's not equals to 10 (error) [%field] [%realfield] [%value] [%comp]", 10)
    ->condition("eq", "It's not equals to 30 (info)", 30, 'info')
    ->ifFailThenDefault()
    ->isArray(false)
    ->isColumn(true)
    ->set([1,'aaa','bbbb'], 'id',"some error message");


$r2 = $val->def('ERROR')
    ->type('integer')
    ->ifFailThenDefault()
    ->required()->get('id',"Missing id field from get");


$timeEnd = microtime(true);
echo json_encode($val->messageList->items,JSON_PRETTY_PRINT);
echo "<hr>";
echo json_encode(messages()->allArray(),JSON_PRETTY_PRINT);
echo json_encode(messages()->get('id[2]')->allErrorOrWarning(),JSON_PRETTY_PRINT);
echo (messages()->get('id[2]')->firstError())."<br>";

