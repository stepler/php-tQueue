<?php
namespace tQueue\Broker\Storage;

use Exception;

class LocalFS extends Base
{
    protected $queues_dir;

    protected $clear_success;

    protected function parseConfig($config)
    {
        if (empty($config["queues_dir"])) {
            throw new Exception("Undefined 'queues_dir' option");
        }
        if (!file_exists($config["queues_dir"]) ||
            !is_dir($config["queues_dir"])) {
            throw new Exception("Invalid 'queues_dir' option");
        }
        if (empty($config["clear_success"])) {
            $config["clear_success"] = -1;
        }
        if (!is_int($config["clear_success"])) {
            throw new Exception("Invalid 'clear_success' option");
        }

        $this->queues_dir = rtrim($config["queues_dir"], "\/");
    }

    public function find($queue, $status)
    {
        $task_file = $this->findTaskFile($queue, "*", $status);
        if (empty($task_file)) {
            return null;
        }

        return $this->extractTaskData($task_file);
    }

    public function create($id, $queue, $status, $data)
    {
        $task_file = $this->generateTaskFilePath($queue, $id, $status);
        return $this->write($task_file, $data);
    }

    public function update($id, $queue, $status)
    {
        $current_task = $this->findTaskFile($queue, $id, "*");
        $new_task = $this->generateTaskFilePath($queue, $id, $status);

        if (empty($current_task)) {
            return false;
        }

        return $this->rename($current_task, $new_task);
    }

    protected function generateTaskFilePath($queue, $id, $status)
    {
        return sprintf("%s/%s/%s.%s.task", $this->queues_dir, $queue, $id, $status);
    }

    protected function extractTaskData($task_file)
    {
        preg_match("#.*/(?P<queue>.+?)/(?P<id>.+?)\.(?P<status>.+?)\.task$#", $task_file, $matches);
        if (empty($matches["queue"]) ||
            empty($matches["id"]) ||
            empty($matches["status"])) {
            return null;
        }

        $data = $this->read($task_file);
        if (empty($data)) {
            return null;
        }

        return array($matches["id"], $matches["queue"], $matches["status"], $data);
    }

    protected function findTaskFile($queue, $id, $status)
    {
        $file_path = $this->generateTaskFilePath($queue, $id, $status);
        $list = glob($file_path);
        if (empty($list)) {
            return null;
        }
        return $list[0];
    }

    protected function packData($data)
    {
        return serialize($data);
    }

    protected function unpackData($data)
    {
        // @$content = 
        return (@unserialize($data));
    }

    protected function read($file)
    {
        if (!file_exists($file) ||
            !is_readable($file)) {
            return null;
        }

        $raw_content = file_get_contents($file);
        $content = $this->unpackData($raw_content);
        if (empty($content)) {
            return null;
        }

        return $content;
    }

    protected function write($task_file, $data)
    {
        $this->createQueueDir($task_file);

        $data = $this->packData($data);
        $result = file_put_contents($task_file, $data);
        return $result !== false;
    }

    protected function rename($old_task_file, $new_task_file)
    {
        return rename($old_task_file, $new_task_file);
    }

    protected function createQueueDir($task_file)
    {
        $dirname = pathinfo($task_file, PATHINFO_DIRNAME);
        if (file_exists($dirname)) {
            return;
        }

        $result = mkdir($dirname);
        if (!$result) {
            throw new Exception("Unable to create queue folder");
        }
    }
}
