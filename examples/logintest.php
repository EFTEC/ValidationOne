<?php

use eftec\SecurityOneMysql;
use eftec\ValidationOne;
use mapache_commons\Collection;

include __DIR__."/../app_start/App.php";
getDb()->db("securitytest");
$sec=new SecurityOneMysql();

$sec->loginScreen();