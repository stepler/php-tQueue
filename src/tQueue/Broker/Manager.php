<?php
namespace tQueue\Broker;

use tQueue\Broker\Loader;
use tQueue\Task;
use tQueue\Helper\FS;

class Manager extends \tQueue\Base\Manager
{
    protected $broker;

    protected $broker_name;

    protected $broker_settings;

    protected $stat;

    protected $config;

    protected function parseConfig($config) 
    {
        if (empty($config["broker"])) {
            throw new \InvalidArgumentException("Unable to found 'broker' option in broker config");
        }
        if (empty($config["settings"])) {
            throw new \InvalidArgumentException("Unable to found 'settings' option in broker config");
        }

        $this->broker_name = $config["broker"];
        $this->broker_settings = $config["settings"];
    }

    public function on_construct()
    {
        $ns_broker_class = Loader::getBrokerClass($this->broker_name);
        $this->broker = new $ns_broker_class($this->broker_settings);

        $this->stat = $this->tQueue->stat->getClient();
    }

    protected function loadBroker($broker_class)
    {
        $ns_broker_class = __NAMESPACE__."\\Storage\\{$broker_class}";

        if (class_exists($ns_broker_class)) {
            return;
        }

        $storage_path = FS::joinPaths(__DIR__, "Storage");
        $search_filename = strtolower($broker_class);
        $files = FS::findFiles($storage_path, "*.php");
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
