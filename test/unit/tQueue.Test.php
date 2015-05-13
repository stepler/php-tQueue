<?php

use tQueue\Task;

class tQueueTest extends PHPUnit_Framework_TestCase
{
    public function testCreateTask()
    {
        $task = tQueue::add('default', '...');
        $this->assertInstanceOf('\\tQueue\\Task', $task);
    }
}