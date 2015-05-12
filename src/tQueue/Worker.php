<?php
namespace tQueue;

class Worker 
{
    protected $queue;

    protected $callback;

    protected $interval;

    public function __construct($queue, $callback, $options)
    {
        $this->queue = $queue;
        $this->callback = $callback;

        $this->interval = $options["interval"];
    }

    public function work()
    {
        while (true) 
        {
            $task = $this->check_work();
            var_dump($task);
            if ($task) {
                $this->do_work($task);
                continue;
            }

            usleep($this->interval*1000000);
        }
    }

    protected function check_work()
    {
        return TQ::process($this->queue);
    }

    protected function do_work($task)
    {
        $task->running();
        call_user_func_array($this->callback, array($task));
    }
}