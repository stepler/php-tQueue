<?php

class SecondCorrect_Worker extends tQueue\Worker\Worker
{
    protected $forks = 2;

    protected $queue = "default";

    public function process($taskId, $taskData)
    {
        
    }
}

class ThirdCorrect_Worker extends tQueue\Worker\Worker
{
    protected $forks = 2;

    protected $queue = "default";

    public function process($taskId, $taskData)
    {
        
    }
}
