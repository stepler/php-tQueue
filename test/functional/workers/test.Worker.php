<?php

class ParseSomething_Worker extends tQueue\Worker\Worker
{
    protected $forks = 2;

    protected $queue = "default";

    public function process($taskId, $taskData)
    {
        $this->logger->info("i do my work");
    }
}
