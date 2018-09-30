<?php

use mapache_commons\Debug;

include "../app_start/SecurityOne.php";
include "../autoload.php";
$sec=new SecurityOne();

$sec->setIsAllowedFn(function(SecurityOne $sec,$where,$when,$id) {
    switch($where) {
        case 'test':
            return $sec->isMember("admin");
            break;
        default:
            return false; // not allowed
    }
    });

$sec->setPermissionFn(function(SecurityOne $sec,$where,$when,$id) {
    switch($where) {
        case 'test':
            return ($sec->isMember("admin")?SecurityOne::ADMIN:SecurityOne::NOTALLOWED);
            break;
        default:
            return SecurityOne::NOTALLOWED; // not allowed
    }
    });

// isAllowed =yes, no.
// getPermission = none,view,edit,delete,admin,all

$user=$sec->getSession();

Debug::var_dump($sec->isAllowed("test"));

Debug::var_dump($sec->getPermission("test"));

Debug::var_dump($user);

