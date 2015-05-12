<?php
use STQ\TQ;

require __DIR__."/../bootstrap.php";
require __DIR__."/config.php";

TQ::setConfig($config);

TQ::register_worker("default", function($task) {
    $task->complete();
});

TQ::launch_workers();
