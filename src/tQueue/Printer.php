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
        foreach ($data as $info) {
            $draw_data[] = array(
                $info["group"], $info["name"], $info["status"]);
        }

        $table = new \Console_Table();
        $table->setHeaders(array("GROUP", "PROCESS", "STATUS"));
        $table->addData($draw_data);
        echo $table->getTable();
    }
}