<?php
namespace tQueue;

use Exception;
use RuntimeException;

class WorkerManager
{
    protected $workers = array();

    protected $workers_pid = array();

    public function register($queue, $worker_callback, $options) 
    {
        $options = $this->parseOptions($options);

        $this->workers[] = array(
            "forks" => $options["forks"],
            "queue" => $queue,
            "callback" => $worker_callback,
            "options" => $options
        );
    }

    protected function parseOptions($options)
    {
        $options["forks"] = 1;
        $options["interval"] = 5;
        return $options;
    }

    public function launch()
    {
        foreach ($this->workers as $worker) {
            for ($i=0; $i<$worker["forks"]; $i++) {
                $this->startWorker($worker["queue"], $worker["callback"], $worker["options"]);
                break;
            }
        }
    }

    protected function startWorker($queue, $callback, $options)
    {
        $pid = $this->fork();
        var_dump($pid);

        // if ($pid === -1) {
        //     exit('Could not fork worker');
        // }
        // if ($pid) {
        //     $this->workers_pid[] = $pid;
        //     return;
        // }

        $worker = new Worker($queue, $callback, $options);
        $worker->work();
    }

    protected function fork()
    {
        var_dump(function_exists('pcntl_fork'));
        if (!function_exists('pcntl_fork')) {
            return -1;
        }

        $pid = pcntl_fork();
        if($pid === -1) {
            throw new RuntimeException('Unable to fork child worker.');
        }

        return $pid;
    }
}