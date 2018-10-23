<?php

use eftec\ValidationOne;


include "common.php";

function customval($value,$compareValue) {
    return true;
}



echo "<h1>Testing to fetch value from set (test array)</h1>";

$countries=[
    ["122","Chile",17],
    ["2","USA",300],
    ["3","Canada",'aaa'],
    ["aaa","Mexico",30],
    ["3","Japan",'170']
];

//->friendId("ID-ARRAY")
    //getVal()->reset();
foreach($countries as $id=>$country) {
    echo "<hr><b>$id: </b><br>";
    echo "input:";
    var_dump($country);
    $r = getVal()->def(['ERROR ID', 'ERROR NAME','ERROR'])
        ->type(['integer', 'string','integer'])
        ->condition('lt','%field %key must be less than %comp',10,'error',0)
        ->condition('betweenlen','',[1,5],'error',1)
        ->successMessage('form','Operation successful')
        ->ifFailThenDefault()
        ->isColumn(false)
        ->isArray(true)->set($country, "country $id", '');

    echo "<br>";
    echo "output:";
    var_dump($r);
    echo "<br>";
}
echo "<hr>";



echo "The fetch obtained is :";
echo "<pre>";
var_dump($r);
echo "</pre>";
echo "<hr>All messages:<br>";
echo "<pre>";
var_dump(getVal()->messageList->allArray());
echo "</pre>";

echo "<hr>Messages keys:<br>";
echo json_encode(getVal()->messageList->allIds(),JSON_PRETTY_PRINT);


echo "<hr>All error or warning:<br>";
echo json_encode(messages()->get('id[2]')->allErrorOrWarning(),JSON_PRETTY_PRINT);
echo "<hr>First error on id[2]:<br>";
echo (messages()->get('id[2]')->firstError())."<br>";

