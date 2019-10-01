<?php

use eftec\ValidationOne;


include "common.php";
include "Someclass.php";
function customval($value,$compareValue) {
    return ($value==$compareValue);
}





$r=getVal()->def(null)
    ->type('integer')
    ->get("id");

echo "<h1>Testing to fetch value from get</h1>";
echo "<a href='?frm_id=12345'>click here for test a value</a><br><br>";

echo "The value of id is [<b>".print_r($r,true)."</b>]<br><br>";

echo "[id] Errors and warnings:";
echo "<pre>";
var_dump(getVal()->messageList->get('id')->allErrorOrWarning());
echo "</pre>";
echo "[id] container:";
echo "<pre>";
var_dump(getVal()->messageList->get("id"));
echo "</pre>";
