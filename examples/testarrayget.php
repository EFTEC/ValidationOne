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

echo "<h1>Testing to fetch value from get (test array)</h1>";
echo "<a href='?frm_id[0]=1&frm_id[1]=2&frm_id[2]=3'>click here for test a value</a><br><br>";


$example=new Example();

function getExample() {
    echo "injecting..";
    return new Example();

}


    //getVal()->reset();
$r = getVal()->def('ERROR')
    ->friendId("Id Field")
    ->type('integer')->ifFailThenDefault()
    ->isColumn(true)
    ->successMessage('','info:Fetch successful')
    ->isArray(false)->get('id',"some error message on %field");

getVal()->hasError();

echo "The fetch obtained is :";
var_dump($r);
echo "<hr>All messages:<br>";
echo json_encode(getVal()->messageList->allArray(),JSON_PRETTY_PRINT);
echo "<hr>Messages keys:<br>";
echo json_encode(getVal()->messageList->allIds(),JSON_PRETTY_PRINT);


echo "<hr>All error or warning:<br>";
echo json_encode(messages()->get('id[2]')->allErrorOrWarning(),JSON_PRETTY_PRINT);
echo "<hr>First error on id[2]:<br>";
echo (messages()->get('id[2]')->firstError())."<br>";

