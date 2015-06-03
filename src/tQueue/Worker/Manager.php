<?php
namespace tQueue\Worker;

use stdClass;
use tQueue\Worker\Pid;
use tQueue\Worker\Loader;
use tQueue\Helper\Tools;
use tQueue\Helper\Validate;

class Manager extends \tQueue\Base\Manager
{
    protected $workers;

    protected $loader;

    public function parseConfig($config)
    {
        if (empty($config["workers_dir"])) {
            throw new \InvalidArgumentException("Unable to found 'workers_dir' option in workers config");
        }

        $this->loader = new Loader($config["workers_dir"]);
    }

    protected function loadWorkers()
    {
        $list = array();
        $classes = $this->loader->getWorkers();
        foreach ($classes as $worker_class)
        {
            $worker = new $worker_class($this->tQueue, 1);
            $list[] = $worker;

            for ($fork=2; $fork<=$worker->getForks(); $fork++) {
                $list[] = new $worker_class($this->tQueue, $fork);
            }
        }
        return $list;
    }

    protected function getWorkers()
    {
        if (is_null($this->workers)) {
            $this->workers = $this->loadWorkers();
        }

        return $this->workers;
    }

    protected function getWorker($name, $fork)
    {
        $workers = $this->getWorkers();
        foreach ($workers as $worker)
        {
            if ($worker->getName() == $name && 
                $worker->getFork() == $fork) {
                return $worker;
            }
        }
        return null;
    }

    public function stop()
    {
        $this->process->setStopping(null, "workers");
    }

    public function start()
    {
        $workers = $this->getWorkers();
        foreach ($workers as $worker)
        {
            if (!$this->process->isStopped($worker->getForkName())) {
                continue;
            }
            $this->process->setGroup($worker->getForkName(), "workers");
            $pid = $this->launchWorker($worker->getName(), $worker->getFork());
            if ($this->process->statusProcess($pid)) {
                $this->process->setPid($pid, $worker->getForkName());
                $this->logger->info("Worker is started. PID: {$pid}");
            }
            else {
                $this->logger->error("Unable to start worker process: ".$worker->getForkName());
            }
        }
    }

    protected function launchWorker($name, $fork) 
    {
        return $this->tQueue->launchProcess(
            array("TYPE"=>"worker", "WORKER"=>$name, "FORK"=>$fork));
    }

    public function startWorkerProcess($name, $fork)
    {
        $worker = $this->getWorker($name, $fork);
        if (!$worker) {
            $this->logger->error("Trying launch undefined worker '{$name}.{$fork}'");
        }
        if (!$this->process->isStopped($worker->getForkName())) {
            $this->logger->error("Trying launch not stopped worker '{$name}.{$fork}'");
            return;
        }
        $this->process->setLaunched($worker->getForkName());
        $worker->run();
        $this->process->setStoped($worker->getForkName());
    }
}
