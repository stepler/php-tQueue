<?php
namespace tQueue\Worker;

use tQueue\Helper\Validate;
use tQueue\Process;
use Exception;

abstract class Worker 
{
    protected $queue;

    protected $interval = 5;

    protected $logger;

    protected $broker;

    protected $forks;
    protected $fork;

    protected $remove_completed_task = false;

    protected $name;

    public $shutdown = false;

    final public function __construct($tQueue, $config, $fork)
    {
        Validate::workerName($this->getName());

        if (isset($config["remove_task_after_complete"]) &&
            !!($config["remove_task_after_complete"]) == true) {
            $this->remove_completed_task = true;
        }

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

        $task = $this->broker->process($this->queue);
        if ($task) {
            $this->do_work($task);
        }

        return true;
    }

    protected function do_work($task)
    {
        $this->logger->info("Running task {queue}#{task_id}", 
            array("queue"=>$this->queue, "task_id"=>$task->getId()));

        $task->running();
        try {
            $result = $this->process($task->getId(), $task->getData());
            $task->complete();
            $this->stat_client->update($task->getQueue(), $this->getName(), $task->getStatus());
            $this->logger->info("Competed task {queue}#{task_id}", 
                array("queue"=>$this->queue, "task_id"=>$task->getId()));

            if ($this->remove_completed_task) {
                $this->broker->remove($task->getId());
            }
        }
        catch (Exception $e) {
            $task->failed();
            $this->stat_client->update($task->getQueue(), $this->getName(), $task->getStatus());
            $this->logger->error("Throw Error task {queue}#{task_id}: {error}", 
                array("queue"=>$this->queue, "task_id"=>$task->getId(), "error"=>$task->getMessage()));
        }
    }

    public function run()
    {
        usleep(mt_rand(0, 1000000));

        $this->setUp();
        while ($this->work()) {
            usleep($this->interval * 1000000);
            $this->shutdown();
        }
        $this->tearDown();
    }

    public function shutdown()
    {
        if ($this->process->isLaunched($this->getForkName())) {
            return;
        }
        $this->logger->info('Shutting down...');
        $this->shutdown = true;
    }

    protected function setUp() {}
    protected function tearDown() {}
    abstract protected function process($taskId, $taskData);
}