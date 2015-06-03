<?php
namespace tQueue\Broker\Storage;

use tQueue\Helper\FS;
use tQueue\Helper\Validate;

class LocalFS extends Base
{
    protected $queues_dir;

    protected $clear_success;

    protected function parseConfig($config)
    {
        if (empty($config["queues_dir"])) {
            throw new InvalidArgumentException("Unable to found 'queues_dir' option in broker config");
        }
        Validate::directory($config["queues_dir"]);

        if (isset($config["clear_success"]) && !is_int($config["clear_success"])) {
            throw new InvalidArgumentException("Invalid 'clear_success' option in broker config");
        }

        if (!isset($config["clear_success"])) {
            $config["clear_success"] = -1;
        }
        $this->queues_dir = $config["queues_dir"];
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
        $task_file = $this->findTaskFile($queue, $id, "*");
        $new_task_file = $this->generateTaskFilePath($queue, $id, $status);

        if (empty($task_file)) {
            return false;
        }

        return FS::renameFile($task_file, $new_task_file);
    }

    public function delete($id, $queue)
    {
        $task_file = $this->findTaskFile($queue, $id, "*");
        if (empty($task_file)) {
            return false;
        }

        return FS::deleteFile($task_file);
    }

    protected function generateTaskFilePath($queue, $id, $status)
    {
        return FS::joinPaths($this->queues_dir, $queue, sprintf("%s.%s.task", $id, $status));
    }

    protected function extractTaskData($task_file)
    {
        preg_match("#.*/(?P<queue>.+?)[\/](?P<id>.+?)\.(?P<status>.+?)\.task$#", $task_file, $matches);
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
        return (@unserialize($data));
    }

    protected function read($file)
    {
        $raw_content = FS::readFile($file);
        if (empty($raw_content)) {
            return null;
        }
        
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
        return FS::writeFile($task_file, $data);
    }

    protected function createQueueDir($task_file)
    {
        $dirname = pathinfo($task_file, PATHINFO_DIRNAME);
        if (file_exists($dirname)) {
            return;
        }

        $result = mkdir($dirname);
        if (!$result) {
            throw new \RuntimeException("Unable to create queue folder");
        }
    }
}
