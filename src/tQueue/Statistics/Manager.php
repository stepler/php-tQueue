<?php
namespace tQueue\Statistics;

use tQueue\Statistics\Server;
use tQueue\Statistics\Client;
use tQueue\Tools;

class Manager 
{
    protected $socket;

    protected $logger;

    protected $pid_file;

    public function __construct($config)
    {
        $this->config = $config;
        $this->pid_file = $config["pid_file"];
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function getClient()
    {
        return new Client($this->config["host"]);
    }

    public function start()
    {
        $pid = \tQueue::fork();
        if ($pid === 0) {
            $s = new Server($this->config);
            $s->run();
            return;
        }

        $this->setPid($pid);
        $this->logger->info("Statistics server is started");
    }

    public function stop()
    {
        $pid = $this->getPid();
        if (empty($pid)) {
            return;
        }

        posix_kill($pid, SIGQUIT);
        $this->setPid(null);
        $this->logger->info("Statistics server is stopped");
    }

    protected function setPid($pid)
    {
        if (empty($pid)) {
            $pid = "";
        }

        $result = file_put_contents($this->pid_file, $pid);
        if ($result === false) {
            Tools::killProcess($pid);
            throw new \Exception("Unable to save PID to {$this->pid_file}");
        }
    }

    protected function getPid()
    {
        @$content = file_get_contents($this->pid_file);
        if (empty($content)) {
            return null;
        }
        return $content;
    }

    public function getData()
    {
        $s = new Server($this->config);
        return $s->getData()->getArray();
    }
}
