<?php

use eftec\ValidationOne;


include "common.php";


if (isset($_GET['button'])) {




    //getVal()->reset();
    $col1 = getVal()->def()
        ->friendId("Field #1")
        ->type('integer')
        ->ifFailThenDefault()
        ->ifFailThenOrigin()
        ->successMessage('', 'info:Fetch successful')
        ->isColumn(true)
        ->isArray(false)->get('field1');
    $col2 = getVal()->def(['ERROR','ERROR','ERROR'])
        ->friendId("Field #2")
        ->type('string')
        ->condition('betweenlen','',[2,5],"error")
        ->ifFailThenDefault()
        ->ifFailThenOrigin()
        ->successMessage('', 'info:Fetch successful')
        ->isColumn(true)
        ->isArray(false)->get('field2');

    echo "The fetch obtained is :";
    var_dump($col1);
    echo "<hr>All messages:<br>";
    echo json_encode(getVal()->messageList->allArray(), JSON_PRETTY_PRINT);
    echo "<hr>Messages keys:<br>";
    echo json_encode(getVal()->messageList->allIds(), JSON_PRETTY_PRINT);


    echo "<hr>All error or warning:<br>";
    echo json_encode(messages()->get('id[2]')->allErrorOrWarning(), JSON_PRETTY_PRINT);
    echo "<hr>First error on id[2]:<br>";
    echo (messages()->get('id[2]')->firstError()) . "<br>";

}


?>
<form method="get">
    <table>
        <tr>
            <th>Number field</th>
            <th>String field between 2 to 5 characters</th>
        </tr>
        <tr>
            <td><input type="text" name="frm_field1[]"
                       title="<?=getVal()->messageList->get('field1[0]')->firstError()?>"
                       style="color:<?=(getVal()->messageList->get('field1[0]')->countError())?'red':'black';?>"
                       value="<?=@$col1[0];?>" /></td>
            <td><input type="text" name="frm_field2[]"
                       title="<?=getVal()->messageList->get('field2[0]')->firstError()?>"
                       style="color:<?=(getVal()->messageList->get('field2[0]')->countError())?'red':'black';?>"
                       value="<?=@$col2[0];?>" /></td>

        </tr>
        <tr>
            <td><input type="text" name="frm_field1[]"
                       title="<?=getVal()->messageList->get('field1[1]')->firstError()?>"
                       style="color:<?=(getVal()->messageList->get('field1[1]')->countError())?'red':'black';?>"
                       value="<?=@$col1[1];?>" /></td>
            <td><input type="text" name="frm_field2[]"
                       title="<?=getVal()->messageList->get('field2[1]')->firstError()?>"
                       style="color:<?=(getVal()->messageList->get('field2[1]')->countError())?'red':'black';?>"
                       value="<?=@$col2[1];?>" /></td>
        </tr>
        <tr>
            <td><input type="text" name="frm_field1[]"
                       title="<?=getVal()->messageList->get('field1[2]')->firstError()?>"
                       style="color:<?=(getVal()->messageList->get('field1[2]')->countError())?'red':'black';?>"
                       value="<?=@$col1[2];?>" /></td>
            <td><input type="text" name="frm_field2[]"
                       title="<?=getVal()->messageList->get('field2[2]')->firstError()?>"
                       style="color:<?=(getVal()->messageList->get('field2[2]')->countError())?'red':'black';?>"
                       value="<?=@$col2[2];?>" /></td>
        </tr>
    </table>
    <input type="submit" value="sendme" name="button" />

</form>

<?

echo "<h1>Testing to fetch value from get (test array)</h1>";
echo "<a href='?frm_id[0]=1&frm_id[1]=2&frm_id[2]=3'>click here for test a value</a><br><br>";


