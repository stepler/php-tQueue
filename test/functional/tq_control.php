<?php
date_default_timezone_set("GMT");
require __DIR__."/../../vendor/autoload.php";

$cmd = isset($argv[1]) ? $argv[1] : "";
$tq = tQueue::create(__DIR__."/config.ini");

if ($cmd === "start") {
    $tq->startWorkers();
    $tq->startStat();
}
if ($cmd === "stop") {
    $tq->stopStat();
    $tq->stopWorkers();
}
if ($cmd === "stat") {
    $tq->statistics(true);
    $tq->status(true);
}
if ($cmd === "clear") {
    $tq->clearStatistics();
}
