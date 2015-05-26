<?php
namespace tQueue\Broker;

use tQueue\Task;
use tQueue\Helper\FS;


class Manager extends \tQueue\Base\Manager
{
    protected $broker;

    protected $stat;

    protected $storage_path;

    protected $config;

    protected function parseConfig($config) 
    {
        $this->config = $config;
    }

    public function on_construct()
    {
        $broker_class = $this->config["broker"];
        $broker_settings = $this->config["settings"];
        $this->loadBroker($broker_class);
        
        $ns_broker_class = '\\tQueue\\Broker\\Storage\\'.$broker_class;

        $this->broker = new $ns_broker_class($broker_settings);

        $this->stat = $this->tQueue->stat->getClient();

        $this->storage_path = FS::joinPaths(__DIR__, "Storage");
    }

    protected function loadBroker($broker_class)
    {
        $ns_broker_class = '\\tQueue\\Broker\\Storage\\'.$broker_class;

        if (class_exists($ns_broker_class)) {
            return;
        }

        $search_filename = strtolower($broker_class);
        $files = FS::findFiles($this->storage_path, "*.php");
        foreach ($files as $file) {
            if ($search_filename === strtolower(pathinfo($file, PATHINFO_FILENAME)))  {
                require $file;
                break;
            }
        }

        if (!class_exists($ns_broker_class)) {
            throw new \Exception("Unable to load Broker '{$broker_class}'");
        }
    }

    protected function createTask($queue, $data)
    {
        $id = $this->generateId();
        $status = Task::STATUS_WAITING;
        return $this->getTask($id, $queue, $status, $data);
    }

    protected function getTask($id, $queue, $status, $data)
    {
        $broker = $this->broker;
        $task = new Task($id, $queue, $status, $data);

        $task->setOnSaveCallback(function($task) use ($broker) {
            $broker->update($task->getId(), $task->getQueue(), $task->getStatus());
        });

        return $task;
    }

    protected function generateId()
    {
        return md5(uniqid("", true).time());
    }

    public function add($queue, $data)
    {
        $task = $this->createTask($queue, $data);
        $this->broker->create($task->getId(), $task->getQueue(), $task->getStatus(), $task->getData());

        $this->stat->send($task->getQueue(), null, $task->getStatus());
        
        return $task;
    }

    public function process($queue)
    {
        list($id, $queue, $status, $data) = $this->broker->find($queue, Task::STATUS_WAITING);
        if (empty($id)) {
            return null;
        }
        return $this->getTask($id, $queue, $status, $data);
    }
}
