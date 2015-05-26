<?php

use tQueue\Config;
use tQueue\Helper\Set;

class tQueue 
{
    protected $config;

    protected $container;
    protected $name;

    protected static $instances = array();

    protected function __construct($name, $config_data)
    {
        if (!function_exists('pcntl_fork')) {
            throw new \RuntimeException('PCNTL extension is not installed');
        }

        $this->name = $name;

        $this->container = new Set();

        $this->container["config"] = new Config($config_data);
        $this->container["tQueue"] = $this;

        $this->container->singleton("logger", function($c) {
            return new \tQueue\Logger($c->config->logger);
        });

        $this->container->singleton("stat", function($c) {
            return new \tQueue\Stat\Manager($c->tQueue, $c->config->stat);
        });

        $this->container->singleton("broker", function($c) {
            return new \tQueue\Broker\Manager($c->tQueue, $c->config->broker);
        });

        $this->container->singleton("worker", function($c) {
            return new \tQueue\Worker\Manager($c->tQueue, $c->config->workers);
        });

        self::$instances[$this->name] = $this;
    }

    public static function get($name="default")
    {
        if (isset(self::$instances[$name])) {
            return self::$instances[$name];
        }
        return null;
    }

    public static function create($name_or_config_data, $config_data=null)
    {
        $name = $name_or_config_data;
        if (empty($config_file)) {
            $name = "default";
            $config_data = $name_or_config_data;
        }

        return new self($name, $config_data);
    }

    public function __get($name)
    {
        return $this->container[$name];
    }

    public function __isset($name)
    {
        return isset($this->container[$name]);
    }

    public function add($queue, $data)
    {
        \tQueue\Helper\Validate::queueName($queue);
        return $this->broker->add($queue, $data);
    }

    public function process($queue)
    {
        \tQueue\Helper\Validate::queueName($queue);
        return $this->broker->process($queue);
    }

    public static function getWorkers()
    {
        return $this->worker->getWorkers();
    }

    public function startWorkers()
    {
        $this->worker->start();
    }

    public function stopWorkers()
    {
        $this->worker->stop();
    }

    public function statStart()
    {
        $this->stat->start();
    }

    public function statStop()
    {
        $this->stat->stop();
    }

    public function statData()
    {
        return $this->stat->getData();
    }

    public function statPrint()
    {
        $this->stat->printData();
    }

    public static function fork()
    {
        $pid = pcntl_fork();
        if($pid === -1) {
            throw new \RuntimeException('Unable to fork worker');
        }

        return $pid;
    }
}
