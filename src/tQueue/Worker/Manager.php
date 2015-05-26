<?php
namespace tQueue\Worker;

use tQueue\Worker\Pid;
use tQueue\Worker\Loader;
use tQueue\Helper\Validate;

class Manager extends \tQueue\Base\Manager
{
    protected $workers = array();

    protected $pid_files = array();

    protected $pid;

    protected $logger;

    public function parseConfig($config)
    {
        if (empty($config["pids_dir"])) {
            throw new \InvalidArgumentException("Unable to found 'pids_dir' option in workers config");
        }
        if (empty($config["workers_dir"])) {
            throw new \InvalidArgumentException("Unable to found 'workers_dir' option in workers config");
        }

        $this->config = $config;
        $this->pid = new Pid($config["pids_dir"]);

        $this->logger = $this->tQueue->logger;
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
            $w = new $workerClass(null, null, null);
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
                    $w = new $worker_class(
                        $this->tQueue->broker,
                        $this->tQueue->logger, 
                        $this->tQueue->stat->getClient()
                    );

                    $w->run();
                    break;
                }
                $this->logger->debug("New worker PID: {$pid}");
            }
        }
    }
}
