<?php
date_default_timezone_set('GMT');
require __DIR__."/../../vendor/autoload.php";

$queue = isset($argv[1]) ? $argv[1] : "default" ;
$data = isset($argv[2]) ? $argv[2] : "some_data" ;

$tq = tQueue::create(__DIR__."/config.ini");
$task = $tq->add($queue, $data);
