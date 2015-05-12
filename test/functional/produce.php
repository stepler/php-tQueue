<?php
use STQ\TQ;

require __DIR__."/../bootstrap.php";
require __DIR__."/config.php";

TQ::setConfig($config);
$task = TQ::add("default", array("some_key"=>"some_data"));

print_r($task);