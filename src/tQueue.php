<?php

use tQueue\BrokerManager;
use tQueue\Worker\Manager as WorkerManager;
use tQueue\WorkerLoader;
use tQueue\Tools;
use tQueue\Worker;
use tQueue\Logger;

class tQueue 
{
    protected static $broker_manager;
    protected static $worker_manager;
    protected static $logger;
    
    protected static $config;

    protected static $instance;

    public static function getBrokerManager()
    {
        self::checkConfig();
        if (!static::$broker_manager) {
            static::$broker_manager = new BrokerManager(self::$config["broker"]);
        }
        return static::$broker_manager;
    }

    public static function getWorkerManager()
    {
        self::checkConfig();

        if (!static::$worker_manager) {
            $logger = self::getLogger();

            static::$worker_manager = new WorkerManager(self::$config["workers"]);
            static::$worker_manager->setLogger($logger);
        }

        return static::$worker_manager;
    }

    public static function getLogger()
    {
        self::checkConfig();
        if (!static::$logger) {
            static::$logger = new Logger(self::$config["logger"]);
        }
        return static::$logger;
    }

    protected static function checkConfig()
    {
        if (empty(self::$config)) {
            throw new Exception("You must set config 'tQueue::setConfig(...)' before using tQueue functions");
        }
    }  

    public static function setConfig($config_file)
    {
        if (!empty(self::$config)) {
            return;
        }

        if (!file_exists($config_file) || !is_readable($config_file)) {
            throw new Exception("Invalid config file");
        }

        $config = parse_ini_file($config_file, true);

        if (empty($config["broker"])) {
            throw new Exception("Unable to found broker settings in config '{$config_file}'");
        }
        if (!isset($config["workers"])) {
            throw new Exception("Unable to found workers settings in config '{$config_file}'");
        }
        if (!isset($config["logger"])) {
            throw new Exception("Unable to found logger settings in config '{$config_file}'");
        }

        self::$config = $config;
    }

    public static function add($queue, $data)
    {
        Tools::validateQueue($queue);
        $bm = self::getBrokerManager();

        return $bm->add($queue, $data);
    }

    public static function process($queue)
    {
        Tools::validateQueue($queue);
        $bm = self::getBrokerManager();

        return $bm->process($queue);
    }

    public static function getWorkers()
    {
        $wl = self::getWorkerLoader();
        return $wl->getWorkers();
    }

    public static function startWorkers()
    {
        $wm = self::getWorkerManager();
        $wm->start();
    }

    public static function stopWorkers()
    {
        $wm = self::getWorkerManager();
        $wm->stop();
    }

    public static function fork()
    {
        if (!function_exists('pcntl_fork')) {
            return -1;
        }

        $pid = pcntl_fork();
        if($pid === -1) {
            throw new RuntimeException('Unable to fork worker');
        }

        return $pid;
    }
}
