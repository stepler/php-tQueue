<?php

require __DIR__."/../../vendor/autoload.php";
require __DIR__."/config.php";

tQueue::setConfig($config);
$task = tQueue::add("default", array("some_key"=>"some_data"));

print_r($task);