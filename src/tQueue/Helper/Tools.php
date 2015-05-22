<?php
namespace tQueue\Helper;

class Tools 
{
    public static function validateQueueName($queue_name)
    {
        if (preg_match('/[^\w-]/', $queue_name) !== 0) {
            throw new \InvalidArgumentException(
                "Invalid queue name {$queue_name}. Queue name must contain '0-9a-zA-Z_-' sybmbols");
        }
    }

    public static function validateWorkerName($worker_name)
    {
        if (preg_match('/[^\w-]/', $worker_name) !== 0) {
            throw new \InvalidArgumentException(
                "Invalid worker name {$worker_name}. Worker name must contain '0-9a-zA-Z_-' sybmbols");
        }
    }

    public static function killProcess($pid)
    {
        exec("kill -9 {$pid}");
    }
}