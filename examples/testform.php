<?php

use eftec\ValidationOne;


include "common.php";
include "Someclass.php";

$field1=getVal()->type('integer')
    ->post('field1');
$field2=getVal()->type('string')
    ->condition('minlen','',3)
    ->condition('maxlen','',10)
    ->post('field2');
$field3=getVal()->type('string')
    ->exist(true)

    ->post('field3');
$field4=getVal()->type('string')
    ->exist(true)
    ->post('field4');
$field5=getVal()->type('datetimestring')
    ->def(new DateTime())
    ->ifFailThenDefault(true)
    ->fetch(0,'field5');
$submit=getVal()->type('string')->post('submit');


var_dump(getVal()->messageList->allIds());

?>
<form method="post">
    <h2>Field 1 must be numeric</h2>
    field1:<input type="text" name="frm_field1" value="<?=$field1;?>" /><br/>
    <?php echo getVal()->getMessageId('field1')->firstErrorOrWarning(); ?><br/>
    
    <h2>Field 2 size must be between 3 and 10</h2>
    field2:<input type="text" name="frm_field2" value="<?=$field2;?>" /><br/>
    <?php echo getVal()->getMessageId('field2')->firstErrorOrWarning(); ?><br/>


    <h2>Field 3 is always missing</h2>
    field3:<input type="text"  value="<?=$field3;?>" /><br/>
    <?php echo getVal()->getMessageId('field3')->firstErrorOrWarning(); ?><br/>
    field4:<input type="text" name="frm_field4" value="<?=$field4;?>" /><br/>
    <?php echo getVal()->getMessageId('field4')->firstErrorOrWarning(); ?><br/>

    <h2>Field 5 is a datestring (format:<?php echo getVal()->dateShort ?> -> returned as <?php echo getVal()->dateOutputString ?>)</h2>
    field5:<input type="text" name="frm_field5" value="<?=$field5;?>" /><br/>
    <?php echo getVal()->getMessageId('field5')->firstErrorOrWarning(); ?><br/>
    
    <input type="submit" name="frm_submit" value="submit" /><br/>
</form>
