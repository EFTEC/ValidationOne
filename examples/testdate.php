<?php

use eftec\ValidationOne;


include "common.php";


getVal()->dateShort='m-d-Y';
//getVal()->dateOutputString='d D M Y';

$r=getVal()->def(null)
    ->type('datestring')->set('12-31-2019');

echo "<h1>testing dates</h1>";
echo "The value is ".print_r($r,true)."<br>";

var_dump(getVal()->messageList->allErrorOrWarningArray());
getVal()->messageList->resetAll();
echo "<hr>";

getVal()->dateShort='d-m-Y';
//getVal()->dateOutputString='d D M Y';

$r=getVal()->def(null)
    ->type('datestring')
    ->condition('lte','%valuevalues are not less than %comp','2000-31-12')
    ->set('31-12-2019');


echo "The value is <b>".print_r($r,true)."</b><br>";
echo "Errors or warnings:";
var_dump(getVal()->messageList->allArray());
getVal()->messageList->resetAll();

echo "<hr>";

getVal()->dateShort='d-m-Y';
//getVal()->dateOutputString='d D M Y';
getVal()->messageList->resetAll();
$r2=getVal()->def(null)
    ->type('datestring')
    ->condition('lte','value (%value) must be less or equals than %comp','12-31-2010')
    ->get('date');

echo "Link to set date via get <a href='?frm_date=01-01-2010'>frm_date=01-01-2010</a><br>";
echo "The value read is <b>".print_r($r2,true)."</b><br>";
echo "Errors or warnings:";
var_dump(getVal()->messageList->allErrorOrWarningArray());
getVal()->messageList->resetAll();