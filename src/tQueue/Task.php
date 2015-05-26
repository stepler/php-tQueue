<?php
namespace tQueue;

class Task 
{
    const STATUS_WAITING = 'waiting';
    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETE = 'complete';
    const STATUS_FAILED = 'failed';

    protected $id;

    protected $queue;

    protected $status;

    protected $data;

    protected $log;

    protected $on_save_callback;

    public function __construct($id, $queue, $status, $data)
    {
        if (empty($id)) {
            throw new \InvalidArgumentException("Undefined ID of task");
        }
        if (empty($queue)) {
            throw new \InvalidArgumentException("Undefined $queue of task");
        }
        if (!in_array($status, array(self::STATUS_WAITING, 
            self::STATUS_RUNNING, self::STATUS_COMPLETE, self::STATUS_FAILED))) {
            throw new \InvalidArgumentException("Invalid status value {$status}");
        }
        if (empty($data)) {
            throw new \InvalidArgumentException("Undefined data of task");
        }

        $this->id = $id;
        $this->queue = $queue;
        $this->status = $status;
        $this->data = $data;
    }

    public function complete($result=null, $force_save=true)
    {
        $this->status = self::STATUS_COMPLETE;

        if ($force_save) {
            $this->save();
        }
    }

    public function failed($reason=null, $force_save=true)
    {
        $this->status = self::STATUS_FAILED;
    
        if ($force_save) {
            $this->save();
        }
    }

    public function running($force_save=true)
    {
        $this->status = self::STATUS_RUNNING;
    
        if ($force_save) {
            $this->save();
        }
    }

    public function save()
    {
        if (is_callable($this->on_save_callback)) {
            call_user_func($this->on_save_callback, $this);
        }
    }

    public function getId()
    {
        return $this->id;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getQueue()
    {
        return $this->queue;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setOnSaveCallback($callback)
    {
        $this->on_save_callback = $callback;
    }
}