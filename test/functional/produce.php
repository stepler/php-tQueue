<?php
date_default_timezone_set('GMT');
require __DIR__."/../../vendor/autoload.php";

$tq = tQueue::create(__DIR__."/config.ini");
$task = $tq->add("default", array("some_key"=>"some_data"));