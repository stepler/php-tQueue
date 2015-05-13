<?php

use tQueue\BrokerManager;
use tQueue\WorkerManager;
use tQueue\Logger;

class tQueue 
{
    protected $broker_manager;

    protected $worker_manager;
    
    protected $logger;

    protected static $config;

    protected static $instance;

    public static function getInstance()
    {
        if (!static::$instance) {
            $class = get_called_class();
            static::$instance = new $class();
        }

        return static::$instance;
    }

    private function __construct()
    {
        if (empty(self::$config)) {
            throw new Exception("You must set config before launch");
        }

        $cfg = &self::$config;

        $this->broker_manager = new BrokerManager($cfg["broker"], $cfg["broker_settings"]);
        $this->worker_manager = new WorkerManager();

        Logger::setVerbose($cfg["log_verbose"]);
    }

    public static function setConfig($config)
    {
        if (!empty(self::$config)) {
            return;
        }

        if (!is_array($config)) {
            throw new Exception("Config must be an array");
        }

        if (empty($config["broker"])) {
            throw new Exception("Unable to found 'broker' option");
        }

        if (!isset($config["broker_settings"])) {
            throw new Exception("Unable to found 'broker_settings' option");
        }

        if (!isset($config["log_verbose"])) {
            $config["log_verbose"] = false;
        }

        self::$config = $config;
    }

    public static function add($queue, $data)
    {
        $_this = self::getInstance();
        $_this->validate_queue($queue);

        return $_this->broker_manager->add($queue, $data);
    }

    public static function process($queue)
    {
        $_this = self::getInstance();
        $_this->validate_queue($queue);

        return $_this->broker_manager->process($queue);
    }

    public static function register_worker($queue, $worker_callback, $options=array())
    {
        $_this = self::getInstance();
        $_this->worker_manager->register($queue, $worker_callback, $options);
    }

    public static function launch_workers()
    {
        $_this = self::getInstance();
        $_this->worker_manager->launch();
    }

    protected function validate_queue($queue_name)
    {
        if (preg_match('/[^\w]/', $queue_name) !== 0) {
            throw new Exception("Invalid queue name {$queue_name}");
        }
    }
}
