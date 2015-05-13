<?php
require __DIR__."/../../vendor/autoload.php";
require __DIR__."/config.php";

tQueue::setConfig($config);

tQueue::register_worker("default", function($task) {
    $task->complete();
});

tQueue::launch_workers();
