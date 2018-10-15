<?php

use eftec\ValidationOne;


include "common.php";
include "Someclass.php";

echo "<h1>Testing continue on Error if 200 is equals to 50</h1>";
echo "variable uses abortOnError() while variable2 no<br><br>";


$r=getVal()->def(null)
    ->type('integer')
    ->abortOnError()
    ->condition('eq','%field %value is not equal to %comp ',50)
    ->condition('eq','%field %value is not equal to %comp ',60)
    ->set('aaa','variable');

$r2=getVal()->def(null)
    ->type('integer')
    ->condition('eq','%field %value is not equal to %comp ',50)
    ->condition('eq','%field %value is not equal to %comp ',60)
    ->set('aaa','variable2');

$r3=getVal()->def(null)
    ->type('integer')
    ->isArray(true) // if flats=false then the messages are stored as variable3[0],variable3[1],variable3[2]
    ->condition('eq','%field %value is not equal to %comp ',50)
    ->condition('eq','%field %value is not equal to %comp ',60)
    ->set(['bbb',2,3],'variable3');

$r4=getVal()->def(null)
    ->type('integer')
    ->isArray(false) // if flats=false then the messages are stored as variable3[0],variable3[1],variable3[2]
    ->condition('eq','%field %value is not equal to %comp ',50)
    ->condition('eq','%field %value is not equal to %comp ',60)
    ->set(['bbb',2,3],'variable4');


echo "[variable]='aaa' Errors and warnings:";
dump(getVal()->messageList->get('variable')->allErrorOrWarning());


echo "[variable2]='aaa' Errors and warnings:";
dump(getVal()->messageList->get('variable2')->allErrorOrWarning());

echo "[variable3]=['bbb',2,3] (flat container) Errors and warnings:";
dump(getVal()->messageList->get('variable3')->allErrorOrWarning());

echo "[variable4]=['bbb',2,3] (non flat) Errors and warnings:";
dump(getVal()->messageList->get('variable4')->allErrorOrWarning());
echo "Message containers:";
dump(getVal()->messageList->allIds());

function dump($var) {
    echo "<pre>";
    echo json_encode($var,JSON_PRETTY_PRINT);
    echo "</pre>";

}