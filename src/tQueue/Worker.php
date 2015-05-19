<?php
namespace tQueue;

class Worker 
{
    protected $queue;

    protected $interval = 5;

    public $shutdown = false;

    protected $logger;

    protected $forks;
    protected $name;

    public function __construct()
    {

    }

    final public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    final public function getForks()
    {
        return $this->forks;
    }

    final public function getName()
    {
        if (!empty($this->name)) {
            return $this->name;
        }
        return __CLASS__;
    }

    public function work()
    {
        if ($this->shutdown === true) {
            return false;
        }
        // $this->logger->info("Searching task");

        $task = $this->get_work();

        if ($task) {
            $this->do_work($task);
        }

        return true;
    }

    protected function get_work()
    {
        return \tQueue::process($this->queue);
    }

    protected function do_work($task)
    {
        $this->logger->info("Running task {task_id}", array("task_id"=>$task->getId()));

        $task->running();
        try {
            $this->process($task);
            $this->logger->info("Task {task_id} is processed", array("task_id"=>$task->getId()));
        }
        catch (Exception $e) {
            $this->logger->error("Task {task_id} throw error: {error}", 
                array("task_id"=>$task->getId(), "error"=>$task->getMessage()));
        }
    }

    public function run()
    {
        declare(ticks = 1);
        pcntl_signal(SIGQUIT, array($this, 'shutdown'));

        usleep(mt_rand(0, 1000000));
        while ($this->work()) {
            usleep($this->interval * 1000000);
        }
    }

    public function shutdown()
    {
        $this->logger->info('Shutting down...');
        $this->shutdown = true;
    }
}