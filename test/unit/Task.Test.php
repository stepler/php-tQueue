<?php

use tQueue\Task;

class TaskTest extends PHPUnit_Framework_TestCase
{
    public function testNewTaskStatus()
    {
        $task = tQueue::add('default', '...');
        $this->assertEquals($task->getStatus(), Task::STATUS_WAITING);
    }

    public function testRuningTaskStatus()
    {
        $task = tQueue::add('default', '...');
        $task->running();
        $this->assertEquals($task->getStatus(), Task::STATUS_RUNNING);
    }

    public function testCompleteTaskStatus()
    {
        $task = tQueue::add('default', '...');
        $task->complete();
        $this->assertEquals($task->getStatus(), Task::STATUS_COMPLETE);
    }

    public function testFailedTaskStatus()
    {
        $task = tQueue::add('default', '...');
        $task->failed();
        $this->assertEquals($task->getStatus(), Task::STATUS_FAILED);
    }

    public function testTaskData()
    {
        $data = array("some_key"=>"some_val", array(1, 2, 3));
        $task = tQueue::add('default', $data);
        $this->assertEquals($task->getData(), $data);
    }
}