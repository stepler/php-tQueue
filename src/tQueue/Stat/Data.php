<?php
namespace tQueue\Stat;

use tQueue\Task;

class Data 
{
    protected $data;

    public function __construct($raw_data)
    {
        @$data = unserialize($raw_data);
        if (!is_array($data)) {
            $data = array();
        }

        $this->data = $data;
    }

    public function inc($queue, $worker, $type)
    {
        if (!array_key_exists($queue, $this->data)) {
            $this->data[$queue] = array(
                "workers" => array(),
                "tasks" => array(
                    Task::STATUS_WAITING => 0,
                    Task::STATUS_COMPLETE => 0,
                    Task::STATUS_FAILED => 0
                )
            );
        }

        if (!empty($worker) && 
            !in_array($worker, $this->data[$queue]["workers"])) {
            $this->data[$queue]["workers"][] = $worker;
        }

        $this->data[$queue]["tasks"][$type] += 1;

        if ($type !== Task::STATUS_WAITING) {
            $this->data[$queue]["tasks"][Task::STATUS_WAITING] -= 1;
        }
    }

    public function getDataString()
    {
        return serialize($this->data);
    }

    public function getArray()
    {
        return $this->data;
    }

    public function printTable()
    {
        $draw_data = array();
        foreach ($this->data as $queue => $data) {
            $row = array(
                $queue,
                implode(",", $data["workers"])
            );
            $row = array_merge($row, $data["tasks"]);

            $draw_data[] = $row;
        }

        $table = new \Console_Table();
        $table->setHeaders(array("QUEUES", "WORKERS", 
            "TASK ".Task::STATUS_WAITING, "TASK ".Task::STATUS_COMPLETE, "TASK ".Task::STATUS_FAILED));
        $table->addData($draw_data);
        echo $table->getTable();
    }
}
