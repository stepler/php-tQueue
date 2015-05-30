<?php

class Correct_Worker extends tQueue\Worker\Worker
{
    protected $forks = 2;

    protected $queue = "default";

    public function process($taskId, $taskData)
    {
        
    }
}
