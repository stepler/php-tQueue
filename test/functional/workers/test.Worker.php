<?php

class ParseSomething_Worker extends tQueue\Worker
{
    protected $forks = 2;

    protected $queue = "default";

    public function process($task)
    {
        $task->complete();
        echo "i do my work";
    }
}