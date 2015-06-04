<?php

class tQueue 
{
    protected $container;
    protected $name;

    protected static $instances = array();

    protected function __construct($name, $config_data)
    {
        $this->name = $name;

        $this->container = new \tQueue\Helper\Set();

        $this->container["config"] = new \tQueue\Config($config_data);
        $this->container["tQueue"] = $this;

        $this->container->singleton("logger", function($c) {
            return new \tQueue\Logger($c->config->logger);
        });

        $this->container->singleton("process", function($c) {
            return new \tQueue\Process\Manager($c->tQueue, $c->config->process, "process");
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

    public function startWorkerProcess($worker_name, $fork)
    {
        $this->worker->startWorkerProcess($worker_name, $fork);
    }

    public function stopWorkers()
    {
        $this->worker->stop();
    }

    public function startStat()
    {
        $this->stat->start();
    }

    public function startStatProcess()
    {
        $this->stat->startServerProcess();
    }

    public function stopStat()
    {
        $this->stat->stop();
    }

    public function statistics($print=false)
    {
        $data = $this->stat->getData();
        if ($print === true) {
            \tQueue\Printer::stat($data);
        }
        return $data;
    }

    public function clearStatistics($print=false)
    {
        $this->stat->clearData();
    }

    public function status($print=false)
    {
        $data = $this->process->status();
        if ($print === true) {
            \tQueue\Printer::status($data);
        }
        return $data;
    }

    public function getProcessBootstrapFile()
    {
        return $this->process->getBootstrap();
    }

    public function launchProcess($args=array())
    {
        $bin = realpath(__DIR__."/../bin/tqueue");
        if (!$bin) {
            throw new \RuntimeException("Unable to find process launcher");
        }

        $args["CONFIG"] = serialize($this->config->getArray());
        $args = implode(" ", array_map(
            function($v, $k) { return sprintf("%s=%s", $k, escapeshellarg($v)); }, $args, array_keys($args)));

        $process_log = $this->process->getLog();
        $output = exec("{$args} php {$bin} >> {$process_log} & echo $!");

        return intval($output) > 0 ? intval($output) : 0 ;
    }
}
