<?php

use tQueue\Task;

class TaskTest extends PHPUnit_Framework_TestCase
{
    public function testPass()
    {
        $this->assertTrue(true);
    }

    public function testInvalidTask1()
    {
        $this->setExpectedException("InvalidArgumentException");
        $task = new Task(1, "...?", "test");
    }

    public function testInvalidTask2()
    {
        $this->setExpectedException("InvalidArgumentException");
        $task = new Task(1, "default", "test");
    }

    public function testTaskQueue()
    {
        $task = new Task(1, "default", Task::STATUS_WAITING);
        $this->assertEquals($task->getQueue(), "default");
    }

    public function testTaskData()
    {
        $data = array("some_key"=>"some_val", array(1, 2, 3));
        $task = new Task(1, "default", Task::STATUS_WAITING, $data);
        $this->assertEquals($task->getData(), $data);
    }

    public function testWaitingTaskStatus()
    {
        $task = new Task(1, "default", Task::STATUS_WAITING);
        $this->assertEquals($task->getStatus(), Task::STATUS_WAITING);
    }

    public function testRuningTaskStatus()
    {
        $task = new Task(1, "default", Task::STATUS_WAITING);
        $task->running(false);
        $this->assertEquals($task->getStatus(), Task::STATUS_RUNNING);
    }

    public function testCompleteTaskStatus()
    {
        $task = new Task(1, "default", Task::STATUS_WAITING);
        $task->complete(false);
        $this->assertEquals($task->getStatus(), Task::STATUS_COMPLETE);
    }

    public function testFailedTaskStatus()
    {
        $task = new Task(1, "default", Task::STATUS_WAITING);
        $task->failed(false);
        $this->assertEquals($task->getStatus(), Task::STATUS_FAILED);
    }

    public function testUpdateTaskByStatusChange()
    {
        $task = new Task(1, "default", Task::STATUS_WAITING);
        $mock = $this->getMock("TaskObserver", array("update"));
        $mock->expects($this->exactly(3))
             ->method("update")
             ->withConsecutive(
                array($this->identicalTo($task)),
                array($this->identicalTo($task)),
                array($this->identicalTo($task))
             );
        $task->setOnSaveCallback(array($mock, "update"));
        $task->running();
        $task->failed();
        $task->complete();
    }
}