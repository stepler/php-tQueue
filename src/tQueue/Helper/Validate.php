<?php
namespace tQueue\Helper;

class Validate 
{
    public static function queueName($queue_name)
    {
        if (preg_match('/[^\w-]/', $queue_name) !== 0) {
            throw new \InvalidArgumentException(
                "Invalid queue name {$queue_name}. Queue name must contain '0-9a-zA-Z_-' sybmbols");
        }
    }

    public static function workerName($worker_name)
    {
        if (preg_match('/[^\w-]/', $worker_name) !== 0) {
            throw new \InvalidArgumentException(
                "Invalid worker name {$worker_name}. Worker name must contain '0-9a-zA-Z_-' sybmbols");
        }
    }

    public static function directory($path, $is_readable=true, $is_writable=true)
    {
        if (!file_exists($path) ||
            !is_dir($path)) {
            throw new \InvalidArgumentException("Invalid directory path: {$path}");
        }
        if ($is_readable && !is_readable($path)) {
            throw new \InvalidArgumentException("Directory is not readable");
        }
        if ($is_writable && !is_writable($path)) {
            throw new \InvalidArgumentException("Directory is not writable");
        }
    }

    public static function file($path, $is_readable=true, $is_writable=true)
    {
        if (!file_exists($path) ||
            is_dir($path)) {
            throw new \InvalidArgumentException("Invalid file path: {$path}");
        }
        if ($is_readable && !is_readable($path)) {
            throw new \InvalidArgumentException("File is not readable");
        }
        if ($is_writable && !is_writable($path)) {
            throw new \InvalidArgumentException("File is not writable");
        }
    }

    public static function makefile($path, $is_readable=true, $is_writable=true)
    {
        if (file_exists($path)) {
            return self::file($path, $is_readable, $is_writable);
        }
        else {
            return self::directory(pathinfo($path, PATHINFO_DIRNAME), false, true);
        }
    }
}