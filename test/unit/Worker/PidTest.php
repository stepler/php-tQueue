<?php

namespace tQueueTest\Worker;

use tQueue\Worker\Pid;

class PidTest extends \PHPUnit_Framework_TestCase 
{
    public function setUp()
    {
        mkdir(WORKERS_PID_DIR, 0777);
    }

    public function tearDown()
    {
        exec("rm -rf ".WORKERS_PID_DIR);
    }

    public function testCreatePidException1()
    {
        $this->setExpectedException('InvalidArgumentException');
        $pid = new Pid(null);
    }

    public function testCreatePidException2()
    {
        $this->setExpectedException('InvalidArgumentException');
        $pid = new Pid(WORKERS_PID_DIR."xxx");
    }

    public function testCreatePid()
    {
        $pid = new Pid(WORKERS_PID_DIR);
        $this->assertEmpty($pid->getAll());
    }

    public function testAddPid()
    {
        $file = WORKERS_PID_DIR."some_name.1.pid";
        $pid = new Pid(WORKERS_PID_DIR);
        $pid->add(10, "some_name", 1);
        $this->assertTrue(file_exists($file));
        $this->assertEquals(10, file_get_contents($file));
        $this->assertEquals(array(10), $pid->getAll());
    }

    public function testAddPid2()
    {
        $pid = new Pid(WORKERS_PID_DIR);
        $pid->add(10, "some_name", 1);
        $pid->add(11, "some_name", 2);
        $pid->add(20, "another_name", 1);
        $pid->add(21, "another_name", 2);
        $pid->add(30, "some_another_name", 1);
        $pid->add(31, "some_another_name", 1); // <-- update
        $this->assertEmpty( array_diff(array(10,11,20,21,31), $pid->getAll()) );
    }

    public function testRemovePid()
    {
        $pid = new Pid(WORKERS_PID_DIR);
        $pid->add(10, "some_name", 1);
        $pid->add(20, "some_name", 2);
        $pid->remove(20);
        $this->assertEquals(array(10), $pid->getAll());
    }

    public function testRemovePid2()
    {
        $pid = new Pid(WORKERS_PID_DIR);
        $pid->add(10, "some_name", 1);
        $pid->add(20, "some_name", 2);
        $pid->remove(30);
        $this->assertEmpty( array_diff(array(10, 20), $pid->getAll()) );
    }

    public function testGetPidByWorkerName()
    {
        $pid = new Pid(WORKERS_PID_DIR);
        $pid->add(99, "some_name", 1);
        $this->assertEquals(99, $pid->getByWorker("some_name", 1));
    }

    public function testGetZeroByInvalidWorkerName()
    {
        $pid = new Pid(WORKERS_PID_DIR);
        $pid->add(99, "some_name", 1);
        $this->assertEquals(0, $pid->getByWorker("some_name", 5));
    }

    public function testZombiePids()
    {
        $pid = new Pid(WORKERS_PID_DIR);
        $pid->add(10, "some_name", 1);
        $pid->add(11, "some_name", 2);
        $pid->add(12, "some_name", 3);
        $pid->add(20, "another_name", 1);
        $pid->add(21, "another_name", 2);
        $pid->add(30, "undefined_name", 1);
        $pid->add(31, "undefined_name", 2);

        $workers = array(
            ((object) array("name"=>"some_name", "forks"=>2)),
            ((object) array("name"=>"another_name", "forks"=>1)),
        );
        $pid = new Pid(WORKERS_PID_DIR);
        $pid->setWorkers($workers);
        $this->assertEmpty( array_diff(array(12,21,30,31), $pid->getZombie()) );
    }
}