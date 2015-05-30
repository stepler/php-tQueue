<?php
date_default_timezone_set('GMT');
require __DIR__."/../../vendor/autoload.php";

$cmd = isset($argv[1]) ? $argv[1] : "";
$tq = tQueue::create(__DIR__."/config.ini");

if ($cmd === 'start') {
    $tq->statStart();
    $tq->startWorkers();
}
if ($cmd === 'stop') {
    $tq->statStop();
    $tq->stopWorkers();
}
if ($cmd === 'stat') {
    $tq->statPrint();
}
