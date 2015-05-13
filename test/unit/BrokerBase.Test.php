<?php

use tQueue\Task;
use tQueue\Broker\TestBroker;

class BrokerBaseTest extends PHPUnit_Framework_TestCase
{
    protected static $broker;

    protected function setUp()
    {
        self::$broker = TestBroker::$instance;
        if (!self::$broker instanceof TestBroker) {
            $this->markTestSkipped('Test Broker does not set');
        }
    }

    public function testNewTaskSync()
    {
        $task = tQueue::add('default', '...');
        $task_b = self::$broker->search($task->getId());

        $this->assertFalse(empty($task_b));
        $this->assertEquals($task_b->id, $task->getId());
        $this->assertEquals($task_b->status, Task::STATUS_WAITING);
    }

    public function testRuningTaskSync()
    {
        $task = tQueue::add('default', '...');
        $task->running();

        $task_b = self::$broker->search($task->getId());
        $this->assertEquals($task_b->status, Task::STATUS_RUNNING);
    }

    public function testCompleteTaskStatus()
    {
        $task = tQueue::add('default', '...');
        $task->complete();

        $task_b = self::$broker->search($task->getId());
        $this->assertEquals($task_b->status, Task::STATUS_COMPLETE);
    }

    public function testFailedTaskStatus()
    {
        $task = tQueue::add('default', '...');
        $task->failed();

        $task_b = self::$broker->search($task->getId());
        $this->assertEquals($task_b->status, Task::STATUS_FAILED);
    }
}
