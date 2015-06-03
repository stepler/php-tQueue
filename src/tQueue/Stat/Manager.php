<?php
namespace tQueue\Stat;

use tQueue\Stat\Server;
use tQueue\Stat\Client;
use tQueue\Helper\Validate;

class Manager extends \tQueue\Base\Manager
{
    protected $config;

    public function parseConfig($config)
    {
        $this->config = $config;
    }

    public function getClient()
    {
        return new Client($this->logger, $this->config);
    }

    public function start()
    {
        if (!$this->process->isStopped(Server::$process_name)) {
            return;
        }

        $pid = $this->tQueue->launchProcess(array("TYPE"=>"stat"));
        if ($this->process->statusProcess($pid)) {
            $this->process->setPid($pid, Server::$process_name);
            $this->logger->info("Statistics server is started. PID: {$pid}");
        }
        else {
            $this->logger->error("Unable to start Statistics server process");
        }
    }

    public function startServerProcess()
    {
        if (!$this->process->isStopped(Server::$process_name)) {
            $this->logger->error("Trying launch not stopped server");
            return;
        }

        $this->process->setLaunched(Server::$process_name);
        $server = new Server($this->tQueue, $this->config);
        $server->run();
        $this->process->setStoped(Server::$process_name);
    }

    public function stop()
    {
        $this->process->setStopping(Server::$process_name);
    }

    public function getData()
    {
        $s = new Server($this->tQueue, $this->config);
        return $s->getData()->getArray();
    }
}
