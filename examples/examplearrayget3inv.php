<form method="post">
    <input type='text' name='field[col1][0]' value="cocacola" />
    <input type='text' name='field[col2][0]' value="123" /><br>
    <input type='text' name='field[col1][1]' value="fanta" />
    <input type='text' name='field[col2][1]' value="123" /><br>
    <input type="submit"><br>
</form>
<?php

use eftec\ValidationOne;

include "common.php";

$values=getVal('')->type('integer')->ifFailThenOrigin()->isArray(true)->request('field');
echo "<h1>The validation</h1>";
var_dump(getVal('')->getMessageId('field')->allError());
//var_dump(getVal('')->getMessage());
echo "<h1>The values</h1>";
echo "<pre>";
var_dump($values);
echo "</pre>";
echo "<h1>The values inverted</h1>";
echo "<pre>";
var_dump(ValidationOne::invertArray($values));
echo "</pre>";

