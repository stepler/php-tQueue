<?php
date_default_timezone_set('GMT');
require __DIR__."/../../vendor/autoload.php";

tQueue::setConfig(__DIR__."/config.ini");
tQueue::launchWorkers();
// tQueue::register_worker("default", function($task) {
//     $task->complete();
// });
// $workers = tQueue::getWorkers();

// foreach ($workers as $workerClass)
// {
//     $pid = tQueue::fork();
//     if ($pid === 0) {
//         $w = new $workerClass();
//         $w->run();
//         break;
//     }
//     echo "{$pid}\n";
// }



// tQueue::launch_workers();

// print_r(get_declared_classes());

// $wm = new \tQueue\WorkerManager();
// $wm->register('a', function(){});
// $wm->register('b', function(){});
// $wm->launch();

// if ($pid == -1) {
//     die('could not fork');
// } else if ($pid) {
//     echo $pid."\n";
// } else {
//     $t->run();
//     // $worker = new \tQueue\Worker('a', function(){}, array("interval"=>5));
//     // $worker->work();

//     echo "Done\n";
// }

// class T {
//     function run()
//     {
//         $worker = new \tQueue\Worker('a', function(){}, array("interval"=>5));
//         $worker->work();
//     }
// }