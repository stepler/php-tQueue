<?php
namespace tQueue\Worker;

use Exception;
use RuntimeException;
use tQueue\Worker\Pid;
use tQueue\Worker\Loader;

class Manager
{
    protected $workers = array();
    protected $pid_files = array();

    protected $pid;

    protected $logger;

    public function __construct($config)
    {
        // TODO Vadidate config
        $this->config = $config;
        $this->pid = new Pid($config["pids_dir"]);
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    protected function getWorkers()
    {
        if (empty($this->workers)) {
            $loader = new Loader($this->config["workers_dir"]);
            $workers = $loader->getWorkers();
            $this->workers = $this->getWorkersInfo($workers);

            $this->pid->setWorkers($this->workers);
        }

        return $this->workers;
    }

    protected function getWorkersInfo($workers)
    {
        $result = array();
        foreach ($workers as $workerClass)
        {
            $w = new $workerClass();
            $result[] = array(
                "class_name" => $workerClass,
                "forks" => $w->getForks(),
                "name" => $w->getName()
            );
            unset($w);
        }

        return $result;
    }

    protected function stopZombie()
    {
        $list = $this->pid->getZombiePids();
        foreach ($list as $pid) {
            $this->stopByPid($pid);
        }
    }

    public function stop()
    {
        $list = $this->pid->getAll();
        foreach ($list as $pid) {
            $this->stopByPid($pid);
        }
    }

    protected function stopByPid($pid)
    {
        $status = $this->statusByPid($pid);
        if ($status) {
            posix_kill($pid, SIGQUIT);
        }
        $this->pid->remove($pid);
    }

    protected function statusByPid($pid)
    {
        exec("ps -o pid,state -p {$pid}", $output, $returnCode);
        return $returnCode === 0;
    }

    protected function status($worker_name, $fork=1)
    {
        $pid = $this->pid->getByWorker($worker_name, $fork);
        return $this->statusByPid($pid);
    }

    public function start()
    {
        $workers = $this->getWorkers();

        $this->stopZombie($workers);

        foreach ($workers as $worker)
        {
            $worker_name = $worker["name"];
            $worker_class = $worker["class_name"];
            $worker_forks = $worker["forks"];

            for ($fork=1; $fork<=$worker_forks; $fork++)
            {
                if ($this->status($worker_name, $fork)) {
                    continue;
                }

                $pid = \tQueue::fork();
                if ($pid > 0) {
                    $this->pid->add($pid, $worker_name, $fork);
                }
                else {
                    $w = new $worker_class();
                    $w->setLogger($this->logger);
                    $w->run();
                    break;
                }
                $this->logger->debug("New worker PID: {$pid}");
            }
        }
    }
}
