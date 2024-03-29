<?php

use eftec\MessageContainer;
use eftec\ValidationOne;
include "../lib/ValidationOne.php";
include "../lib/ValidationItem.php";
include "../vendor/eftec/messagecontainer/lib/MessageContainer.php";
include "../vendor/eftec/messagecontainer/lib/MessageLocker.php";
include "../lib/ValidationInputOne.php";

function messages(): MessageContainer
{
    return MessageContainer::instance();
}

/**
 * @param string $prefix
 * @return ValidationOne
 */
function getVal($prefix='frm_') {
    global $validation;
    if ($validation===null) {
        $validation=new ValidationOne($prefix);
    }
    return $validation;
}

/**
 * @return string
 */
function generateTable($array,$css=true){
    if (!isset($array[0])) {
        $tmp=$array;
        $array=array();
        $array[0]=$tmp;
    } // create an array with a single element
    if ($array[0]===null) {
        return "NULL<br>";
    }
    if ($css===true) {
        $html =
            '<style>.generateTable {
            border-collapse: collapse;
            width: 100%;
        }
        .generateTable td, .generateTable th {
            border: 1px solid #ddd;
            padding: 8px;
        }
        .generateTable tr:nth-child(even){background-color: #f2f2f2;}        
        .generateTable tr:hover {background-color: #ddd;}        
        .generateTable th {
            padding-top: 12px;
            padding-bottom: 12px;
            text-align: left;
            background-color: #4CAF50;
            color: white;
        }
        </style>';
    } else {
        $html='';
    }
    $html .= '<table class="'.(is_string($css)?$css:'generateTable').'">';
    // header row
    $html .= '<thead><tr >';
    foreach($array[0] as $key=>$value){
        $html .= '<th >' . htmlspecialchars($key) . '</th>';
    }
    $html .= '</tr></thead>';

    // data rows
    foreach( $array as $key=>$value){
        $html .= '<tr >';
        foreach($value as $key2=>$value2){
            if (is_array($value2)) {
                $html .= '<td >' . generateTable($value2). '</td>';
            } else {
                $html .= '<td >' . htmlspecialchars($value2) . '</td>';
            }

        }
        $html .= '</tr>';
    }

    // finish table and return it

    $html .= '</table>';
    return $html;
}
