<?php
namespace tQueue;

use Exception;
use RuntimeException;

class WorkerManager
{
    protected $workers = array();

    protected $workers_pid = array();

    protected $logger;

    public function __construct()
    {
        $this->logger = new Logger();
    }

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
        $options["forks"] = 2;
        $options["interval"] = 3;
        return $options;
    }

    public function launch()
    {
        foreach ($this->workers as $worker) {
            for ($i=0; $i<$worker["forks"]; $i++) {
                $this->launchWorker($worker["queue"], $worker["callback"], $worker["options"]);
            }
        }

        print_r($this->workers_pid);
    }

    protected function launchWorker($queue, $callback, $options)
    {
        $pid = $this->fork();

        if ($pid === -1) {
            $this->logger->warning("Could not fork worker for queue {queue}", array("queue"=>$queue));
            exit('Could not fork worker');
        }
        if ($pid) {
            $this->logger->info("Launch for queue {queue}", array("queue"=>$queue));
            $this->workers_pid[] = $pid;
            return;
        }

        $worker = new Worker($queue, $callback, $options);
        $worker->work();
    }

    protected function fork()
    {
        if (!function_exists('pcntl_fork')) {
            $this->logger->error("pcntl module does not set");
            return -1;
        }

        $pid = pcntl_fork();
        if($pid === -1) {
            throw new RuntimeException('Unable to fork worker');
        }

        return $pid;
    }
}