<?php
namespace tQueue;

class Tools 
{
    public static function validateQueue($queue_name)
    {
        if (preg_match('/[^\w]/', $queue_name) !== 0) {
            throw new Exception("Invalid queue name {$queue_name}");
        }
    }

    public static function killProcess($pid)
    {
        exec("kill -9 {$pid}");
    }
}