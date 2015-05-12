<?php
namespace tQueue\Broker;

class TestBroker extends Base 
{
    public $tasks = array();

    protected function parseConfig($config) 
    {

    }

    public function find($queue, $status)
    {
        return $this->search($queue, $status);
    }

    public function create($id, $queue, $status, $data) 
    {
        $task = array($id, $queue, $status, $data);
        $this->tasks[$id] = $task;

        return true;
    }

    public function update($id, $queue, $status) 
    {
        $task = $this->search($id);
        if ($task) {
            return false;
        }

        $task[1] = $queue;
        $task[2] = $status;
        $this->tasks[$id] = $task;

        return true;
    }

    protected function search($id=null, $queue=null, $status=null)
    {
        if ($id) {
            if (empty($this->tasks[$id])) {
                return null;
            }
            return $this->tasks[$id];
        }

        foreach ($this->tasks as $id => $task) 
        {
            if ($queue && $queue != $task[1]) {
                continue;
            }
            if ($status && $status != $task[2]) {
                continue;
            }
            return $task;
        }

        return null;
    }
}