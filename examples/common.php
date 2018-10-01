<?php

use eftec\ErrorList;
use eftec\ValidationOne;
include "../lib/ValidationOne.php";
include "../lib/ValidationItem.php";
include "../lib/ErrorList.php";
include "../lib/ErrorItem.php";

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
 * @return ErrorList|null
 */
function getErrorList() {
    global $errorList;
    if ($errorList===null) {
        $errorList=new ErrorList();
    }
    return $errorList;
}