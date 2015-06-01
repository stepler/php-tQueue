<?php
namespace tQueue;

class Printer
{
    static function stat($data)
    {
        $draw_data = array();
        foreach ($data as $queue => $info) {
            $row = array(
                $queue,
                implode(",", $info["workers"])
            );
            $row = array_merge($row, $info["tasks"]);

            $draw_data[] = $row;
        }

        $table = new \Console_Table();
        $table->setHeaders(array("QUEUES", "WORKERS", 
            "TASK ".Task::STATUS_WAITING, "TASK ".Task::STATUS_COMPLETE, "TASK ".Task::STATUS_FAILED));
        $table->addData($draw_data);
        echo $table->getTable();
    }

    static function status($data)
    {
        $draw_data = array();
        foreach ($data as $worker_info => $status) {
            list($name, $fork) = explode(" # ", $worker_info);
            $row = array($name, $fork,
                ($status ? 'working' : 'stopped' ));
            $draw_data[] = $row;
        }

        $table = new \Console_Table();
        $table->setHeaders(array("WORKERS", "FORK", "STATUS"));
        $table->addData($draw_data);
        echo $table->getTable();
    }
}