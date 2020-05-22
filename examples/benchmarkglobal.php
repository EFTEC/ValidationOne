<?php
echo "<pre>";
$baseVar = str_repeat('x', 1000000);
$GLOBALS['myVar'] = $baseVar;
define('BASEVAR',$baseVar);

const BASEVAR2=BASEVAR;

$group1=["a1"=>"a1","a2"=>"a1","a3"=>"a1","a4"=>"a1"];
$group2=["a1"=>"a1","a2"=>"a1","a3"=>"a1","a4"=>"a1"];
$timeStart = microtime(true);

$r=[];
for($i=0;$i<100000;$i++) {
    $r=array_merge($r,$group2);
}

$timeEnd= microtime(true);

var_dump($timeEnd-$timeStart);
echo "<hr>";

$timeStart = microtime(true);

$r=[];
for($i=0;$i<100000;$i++) {
    foreach($group2 as $item) {
        $r[]=$item;
    }
}

$timeEnd= microtime(true);

var_dump($timeEnd-$timeStart);
die(1);

foreach($items as $item) {
    $r[]=$item;
}



function testfunc_param($paramVar) {
    $localVar = $paramVar;
    return $localVar;
}

function testfunc_global() {
    global $myVar;
    $localVar = $myVar;
    return $localVar;
}

function testfunc_globalsarray() {
    $localVar = $GLOBALS['myVar'];
    return $localVar;
}

function testfunc_globalsarray2() {
    return $GLOBALS['myVar'];
}

function testfunc_const() {
    return BASEVAR;
}

function testfunc_const2() {
    return BASEVAR2;
}

// Testing passing value by parameter
memory_get_usage(); // in case this procs garbage collection
$memoryStart = memory_get_usage(true);
$timeStart = microtime(true);
for ($i = 0; $i < 10000000; $i++) {
    testfunc_param($baseVar);
}
$timeEnd = microtime(true);
$memoryEnd = memory_get_usage(true);
print "Pass value by parameter\nTime: ".($timeEnd - $timeStart)."s\nMemory: ".($memoryEnd-$memoryStart)."\n\n";


// Testing reference to global variable
memory_get_usage(); // in case this procs garbage collection
$memoryStart = memory_get_usage(true);
$timeStart = microtime(true);
for ($i = 0; $i < 10000000; $i++) {
    testfunc_global();
}
$timeEnd = microtime(true);
$memoryEnd = memory_get_usage(true);
print "Global var reference\nTime: ".($timeEnd - $timeStart)."s\nMemory: ".($memoryEnd-$memoryStart)."\n\n";


// Testing reference to global variable via $GLOBALS
memory_get_usage(); // in case this procs garbage collection
$memoryStart = memory_get_usage(true);
$timeStart = microtime(true);
for ($i = 0; $i < 10000000; $i++) {
    testfunc_globalsarray();
}
$timeEnd = microtime(true);
$memoryEnd = memory_get_usage(true);
print "GLOBALS array reference\nTime: ".($timeEnd - $timeStart)."s\nMemory: ".($memoryEnd-$memoryStart)."\n\n";

// Testing reference to global variable via $GLOBALS
memory_get_usage(); // in case this procs garbage collection
$memoryStart = memory_get_usage(true);
$timeStart = microtime(true);
for ($i = 0; $i < 10000000; $i++) {
    testfunc_globalsarray2();
}
$timeEnd = microtime(true);
$memoryEnd = memory_get_usage(true);
print "GLOBALS array reference2\nTime: ".($timeEnd - $timeStart)."s\nMemory: ".($memoryEnd-$memoryStart)."\n\n";

// Testing reference to global variable via $GLOBALS
memory_get_usage(); // in case this procs garbage collection
$memoryStart = memory_get_usage(true);
$timeStart = microtime(true);
for ($i = 0; $i < 10000000; $i++) {
    testfunc_const();
}
$timeEnd = microtime(true);
$memoryEnd = memory_get_usage(true);
print "GLOBALS array const define\nTime: ".($timeEnd - $timeStart)."s\nMemory: ".($memoryEnd-$memoryStart)."\n\n";

// Testing reference to global variable via $GLOBALS
memory_get_usage(); // in case this procs garbage collection
$memoryStart = memory_get_usage(true);
$timeStart = microtime(true);
for ($i = 0; $i < 10000000; $i++) {
    testfunc_const();
}
$timeEnd = microtime(true);
$memoryEnd = memory_get_usage(true);
print "GLOBALS array const\nTime: ".($timeEnd - $timeStart)."s\nMemory: ".($memoryEnd-$memoryStart)."\n\n";


echo "</pre>";