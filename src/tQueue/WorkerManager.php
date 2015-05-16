<?php
namespace tQueue;

use Exception;
use RuntimeException;

class WorkerManager
{
    protected $workers = array();

    protected $workers_pid = array();

    protected $logger;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    protected function getWorkers()
    {
        if (empty($this->workers)) {
            $loader = new WorkerLoader($this->config["workers_dir"]);
            $this->workers = $loader->getWorkers();
        }

        return $this->workers;
    }

    protected function getForksOfWorker($workerClass)
    {
        $w = new $workerClass();
        $forks = $w->getForks();
        unset($w);
        if (!intval($forks)) {
            throw new Exception("Invalid forks value in {$workerClass} class.");
        }
        return $forks;
    }

    public function launch()
    {
        $workers = $this->getWorkers();

        foreach ($workers as $workerClass)
        {
            $forks = $this->getForksOfWorker($workerClass);
            for ($i=0; $i<$forks; $i++)
            {
                $pid = self::fork();
                if ($pid === 0) {
                    $w = new $workerClass();
                    $w->setLogger($this->logger);
                    $w->run();
                    break;
                }
                echo "{$pid}\n";
            }
        }

        // print_r($ this->workers_pid);
    }


    protected function fork()
    {
        if (!function_exists('pcntl_fork')) {
            // $this->logger->error("pcntl module does not set");
            return -1;
        }

        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new RuntimeException('Unable to fork worker');
        }

        return $pid;
    }
}