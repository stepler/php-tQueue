<?php
namespace tQueue;

class Worker 
{
    protected $queue;

    protected $callback;

    protected $interval;

    protected $shutdown;

    protected $logger;

    public function __construct($queue, $callback, $options)
    {
        $this->queue = $queue;
        $this->callback = $callback;

        $this->interval = $options["interval"];

        $this->logger = new Logger();

        $this->registerSigHandlers();
    }

    public function work()
    {
        while (true) 
        {
            if ($this->shutdown === true) {
                break;
            }

            $task = $this->get_work();

            if ($task) {
                $this->do_work($task);
                // continue;
            }

            usleep($this->interval*1000000);
        }
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
            call_user_func_array($this->callback, array($task));
            $this->logger->info("Task {task_id} is processed", array("task_id"=>$task->getId()));
        }
        catch(Exception $e) {
            $this->logger->error("Task {task_id} throw error: {error}", 
                array("task_id"=>$task->getId(), "error"=>$task->getMessage()));
        }
    }

    private function registerSigHandlers()
    {
        if(!function_exists('pcntl_signal')) {
            return;
        }

        declare(ticks = 1);
        pcntl_signal(SIGQUIT, array($this, 'shutdown'));
    }

    protected function shutdown()
    {
        $this->shutdown = true;
    }
}