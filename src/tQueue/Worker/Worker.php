<?php
namespace tQueue\Worker;

use tQueue\Helper\Validate;
use tQueue\Process;

class Worker 
{
    protected $queue;

    protected $interval = 5;

    protected $logger;

    protected $broker;

    protected $forks;
    protected $fork;

    protected $name;

    public $shutdown = false;

    final public function __construct($tQueue, $fork)
    {
        Validate::workerName($this->getName());

        $this->fork = $fork;

        $this->broker = $tQueue->broker;
        $this->logger = $tQueue->logger;
        $this->stat_client = $tQueue->stat->getClient();
        $this->process = $tQueue->process;
    }

    final public function getForks()
    {
        return $this->forks;
    }

    final public function getFork()
    {
        return $this->fork;
    }

    final public function getName()
    {
        if (!empty($this->name)) {
            return $this->name;
        }
        return get_class($this);
    }

    final public function getForkName()
    {
        return $this->getName()."--{$this->fork}";
    }

    public function work()
    {
        if ($this->shutdown === true) {
            return false;
        }

        $task = $this->get_work();

        if ($task) {
            $this->do_work($task);
        }

        return true;
    }

    protected function get_work()
    {
        return $this->broker->process($this->queue);
    }

    protected function do_work($task)
    {
        $this->logger->info("Running task {task_id}", array("task_id"=>$task->getId()));

        $task->running();
        try {
            $result = $this->process($task->getId(), $task->getData());
            $task->complete();
            $this->stat_client->send($task->getQueue(), $this->getName(), $task->getStatus());
            $this->logger->info("Task {task_id} is processed", array("task_id"=>$task->getId()));
        }
        catch (Exception $e) {
            $task->failed();
            $this->stat_client->send($task->getQueue(), $this->getName(), $task->getStatus());

            $this->logger->error("Task {task_id} throw error: {error}", 
                array("task_id"=>$task->getId(), "error"=>$task->getMessage()));
        }
    }

    public function run()
    {
        usleep(mt_rand(0, 1000000));
        while ($this->work()) {
            usleep($this->interval * 1000000);
            $this->shutdown();
        }
    }

    public function shutdown()
    {
        if ($this->process->isLaunched($this->getForkName())) {
            return;
        }
        $this->logger->info('Shutting down...');
        $this->shutdown = true;
    }
}