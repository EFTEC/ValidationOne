<?php
include "../app_start/SecurityOne.php";

$sec=new SecurityOne();

$sec->setLoginFn(
    function(SecurityOne $obj) {
        $obj->group=['admin','user'];
        return true;
    });
$sec->login("aaa","bb") or die("unable to login");


$sec->setIsAllowedFn(function(SecurityOne $sec,$where,$when,$id) {
   switch($where) {
       case 'test':
           return $sec->isMemberGroup("admin");
           break;
       default:
           return false; // not allowed
   }
});

$aa=$sec->getSession();

var_dump($aa);

