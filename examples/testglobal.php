<?php
$GLOBALS['hello']='hello';

function test() {
    var_dump($GLOBALS['hello']);
}

test();