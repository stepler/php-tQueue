<?php
namespace tQueue\Stat;

use tQueue\Stat\Server;
use tQueue\Stat\Client;
use tQueue\Helper\Validate;

class Manager extends \tQueue\Base\Manager
{
    protected $socket;

    protected $logger;

    protected $pid_file;

    public function parseConfig($config)
    {
        if (empty($config["pid_file"])) {
            throw new \InvalidArgumentException("Unable to found 'pid_file' option in stat config");
        }
        Validate::makefile($config["pid_file"]);

        $this->config = $config;
        $this->pid_file = $config["pid_file"];

        $this->logger = $this->tQueue->logger;
    }

    public function getClient()
    {
        return new Client($this->logger, $this->config);
    }

    public function start()
    {
        $pid = \tQueue::fork();
        if ($pid === 0) {
            $s = new Server($this->logger, $this->config);
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
            \tQueue\Helper\Tools::killProcess($pid);
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

    public function printData()
    {
        $s = new Server($this->config);
        return $s->getData()->printTable();
    }
}
