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

    protected function getWorkers()
    {
        if (is_null($this->workers)) {
            $this->workers = $this->loadWorkers();
        }

        return $this->workers;
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

    protected function status($worker_name, $fork=1)
    {
        $pid = $this->pid->getByWorker($worker_name, $fork);
        return Tools::statusProcess($pid);
    }

    public function start()
    {
        $workers = $this->getWorkers();

        $this->stop(true);

        foreach ($workers as $worker)
        {
            $className = $worker->class_name;
            for ($fork=1; $fork<=$worker->forks; $fork++)
            {
                if ($this->status($worker->name, $fork)) {
                    continue;
                }

                $pid = \tQueue::fork();
                if ($pid > 0) {
                    $this->pid->add($pid, $worker->name, $fork);
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
}
