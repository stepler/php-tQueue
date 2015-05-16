<?php
namespace tQueue;

use Exception;
use tQueue\Broker;

class BrokerManager
{
    protected $broker;

    public function __construct($config)
    {
        $broker_class = $config["broker"];
        $broker_settings = $config["settings"];

        $this->loadBroker($broker_class);
        
        $ns_broker_class = '\\tQueue\\Broker\\'.$broker_class;
        $this->broker = new $ns_broker_class($broker_settings);
    }

    protected function loadBroker($broker_class)
    {
        $ns_broker_class = '\\tQueue\\Broker\\'.$broker_class;

        if (class_exists($ns_broker_class)) {
            return;
        }

        $brokers_dir = realpath(__DIR__."/Broker");
        if ($brokers_dir === false) {
            throw new Exception("Unable to found directory with Brokers");
        }

        $search_broker_file = strtolower($broker_class);
        $brokers_list = scandir($brokers_dir);
        foreach ($brokers_list as $broker_file) {
            $req_broker_file = strtolower(basename($broker_file, ".php"));
            if ($search_broker_file === $req_broker_file)  {
                require $brokers_dir.DIRECTORY_SEPARATOR.$broker_file;
                break;
            }
        }

        if (!class_exists($ns_broker_class)) {
            throw new Exception("Unable to load Broker '{$broker_class}'");
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
        return uniqid();
    }

    public function add($queue, $data)
    {
        $task = $this->createTask($queue, $data);
        $this->broker->create($task->getId(), $task->getQueue(), $task->getStatus(), $task->getData());
        
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
