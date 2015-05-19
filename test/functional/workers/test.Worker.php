<?php

class ParseSomething_Worker extends tQueue\Worker\Worker
{
    protected $forks = 2;

    protected $queue = "default";

    public function process($task)
    {
        var_dump($task->getData());
        $this->logger->info("i do my work");
        $task->complete();
    }
}
