<?php
namespace tQueue\Broker;

class TestBroker extends Base 
{
    public static $instance;

    public $tasks = array();

    public function __construct($config)
    {
        self::$instance = $this;
    }
    
    protected function parseConfig($config) {}

    public function find($queue, $status)
    {
        return $this->search($queue, $status);
    }

    public function create($id, $queue, $status, $data) 
    {
        $task = new \stdClass();
        $task->id = $id;
        $task->queue = $queue;
        $task->status = $status;
        $task->data = $data;

        $this->tasks[$id] = $task;

        return true;
    }

    public function update($id, $queue, $status) 
    {
        $task = $this->search($id);
        if (!$task) {
            return false;
        }

        $task->queue = $queue;
        $task->status = $status;

        $this->tasks[$id] = $task;

        return true;
    }

    public function search($id=null, $queue=null, $status=null)
    {
        if ($id) {
            if (empty($this->tasks[$id])) {
                return null;
            }
            return $this->tasks[$id];
        }

        foreach ($this->tasks as $id => $task) 
        {
            if ($queue && $queue != $task->queue) {
                continue;
            }
            if ($status && $status != $task->status) {
                continue;
            }
            return $task;
        }

        return null;
    }
}