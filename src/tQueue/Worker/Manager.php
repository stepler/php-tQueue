<?php
namespace tQueue\Worker;

use tQueue\Worker\Pid;
use tQueue\Worker\Loader;
use tQueue\Helper\Tools;

class Manager extends \tQueue\Base\Manager
{
    protected $workers;

    protected $pid;

    protected $loader;

    public function parseConfig($config)
    {
        if (empty($config["pids_dir"])) {
            throw new \InvalidArgumentException("Unable to found 'pids_dir' option in workers config");
        }
        if (empty($config["workers_dir"])) {
            throw new \InvalidArgumentException("Unable to found 'workers_dir' option in workers config");
        }

        $this->pid = new Pid($config["pids_dir"]);
        $this->loader = new Loader($config["workers_dir"]);
    }

    protected function loadWorkers()
    {
        $workers = array();
        $classes = $this->loader->getWorkers();
        foreach ($classes as $worker_class)
        {
            $w = new $worker_class(null, null, null);
            Validate::workerName($w->getName());

            $inf = new stdClass;
            $inf->class_name = $worker_class;
            $inf->forks = $w->getForks();
            $inf->name = $w->getName();

            $workers[] = $inf;
            unset($w);
        }

        $this->pid->setWorkers($workers);
        return $workers;
    }

    protected function getWorkers()
    {
        if (is_null($this->workers)) {
            $this->workers = $this->loadWorkers();
        }

        return $this->workers;
    }

    protected function getWorkersWithForks()
    {
        $list = array();
        $workers = $this->getWorkers();
        foreach ($workers as $worker)
        {
            for ($fork=1; $fork<=$worker->forks; $fork++) {
                $w = clone($worker);
                $w->fork = $fork;
                $w->full_name = sprintf("%s # %s", $w->name, $w->fork);
                $list[] = $w;
            }
        }
        return $list;
    }

    public function stop($stop_zombie=false)
    {
        $list = $stop_zombie 
                ? $this->pid->getZombie()
                : $this->pid->getAll();

        foreach ($list as $pid) {
            $this->stopByPid($pid);
        }
    }

    protected function stopByPid($pid)
    {
        if (Tools::statusProcess($pid)) {
            posix_kill($pid, SIGQUIT);
        }
        $this->pid->remove($pid);
    }

    public function status()
    {
        $result = array();
        $workers = $this->getWorkersWithForks();
        foreach ($workers as $worker) {
            $result[$worker->full_name] = 
                $this->getWorkerStatus($worker->name, $worker->fork);
        }
        return $result;
    }

    protected function getWorkerStatus($worker_name, $fork)
    {
        $pid = $this->pid->getByWorker($worker->name, $f);
        return Tools::statusProcess($pid);
    }

    public function start()
    {
        $workers = $this->getWorkersWithForks();

        $this->stop(true);

        foreach ($workers as $worker)
        {
            $className = $worker->class_name;
            if ($this->getWorkerStatus($worker->name, $worker->fork)) {
                continue;
            }
            $pid = \tQueue::fork();
            if ($pid > 0) {
                $this->pid->add($pid, $worker->name, $worker->fork);
            }
            else {
                $w = new $className(
                    $this->tQueue->broker,
                    $this->tQueue->logger, 
                    $this->tQueue->stat->getClient()
                );
                $w->run();
                break;
            }
            $this->tQueue->logger->debug("New worker PID: {$pid}");
        }
    }
}
