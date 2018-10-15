<?php

use eftec\MessageList;
use eftec\ValidationOne;
include "../lib/ValidationOne.php";
include "../lib/ValidationItem.php";
include "../lib/MessageList.php";
include "../lib/MessageItem.php";

/**
 * @param string $prefix
 * @return ValidationOne
 */
function getVal($prefix='') {
    global $validation;
    if ($validation===null) {
        $validation=new ValidationOne($prefix);
    }
    return $validation;
}

/**
 * @return MessageList|null
 */
function getMessageList() {
    global $errorList;
    if ($errorList===null) {
        $errorList=new MessageList();
    }
    return $errorList;
}