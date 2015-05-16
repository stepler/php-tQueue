<?php

require __DIR__."/../../vendor/autoload.php";

tQueue::setConfig(__DIR__."/config.ini");
$task = tQueue::add("default", array("some_key"=>"some_data"));
// print_r($task);

// var_dump(posix_kill(909, SIGQUIT));