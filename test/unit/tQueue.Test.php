<?php

use tQueue\Task;

class tQueueTest extends PHPUnit_Framework_TestCase
{
    function testCreateTask()
    {
        $task = tQueue::add('default', 'some_task_data');
        $this->assertInstanceOf('\\tQueue\\Task', $task);
    }
}