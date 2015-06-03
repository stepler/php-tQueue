<?php
namespace tQueue\Stat;

use tQueue\Stat\Manager;
use tQueue\Helper\FS;

class Server 
{
    static $process_name = "Statistics_Server";

    protected $socket;

    protected $logger;
    protected $process;

    protected $save_interval = 60;

    protected $last_save_time = 0;

    protected $accept_timeout = 10;

    protected $data;

    protected $data_file;

    protected $shutdown = false;

    public function __construct($tQueue, $config)
    {
        $this->logger = $tQueue->logger;
        $this->process = $tQueue->process;

        $this->parseConfig($config);
        $this->data = new Data($this->readData());
    }

    protected function parseConfig($config)
    {
        if (empty($config["host"])) {
            throw new \InvalidArgumentException("Unable to found 'host' option in stat config");
        }
        if (empty($config["data_file"])) {
            throw new \InvalidArgumentException("Unable to found 'data_file' option in stat config");
        }

        $this->host = $config["host"];
        $this->data_file = $config["data_file"];

        if (isset($config["save_interval"])) {
            $this->save_interval = (int) $config["save_interval"];
        }
    }

    public function run()
    {
        $socket = stream_socket_server($this->host, $errno, $errstr);
        if (!$socket) {
            throw new \RuntimeException("Unable to create socket server: $errstr ($errno)");
        }

        while (true) {

            if ($this->shutdown === true) {
                $this->saveData(true);
                break;
            }

            @$conn = stream_socket_accept($socket, $this->accept_timeout);
            if ($conn) {
                $data = fgets($conn);
                fclose($conn);
                $this->processData($data);    
            }

            $this->saveData();
            $this->shutdown();
        }

        fclose($socket);
    }

    protected function processData($data)
    {
        list($type, $message) = explode("@", $data);
        if ($type == "counter") {
            $this->updateCounter($message)
        }
        if ($type == "clear") {
            $this->clearCounter($message)
        }
    }

    protected function updateCounter($message)
    {
        list($queue, $worker, $type) = explode("#", $message);
        $this->data->inc($queue, $worker, $type);
    }

    protected function clearCounter()
    {
        $this->data = new Data("");
        $this->saveData(true);
    }

    protected function readData()
    {
        $content = FS::readFile($this->data_file);
        if (empty($content)) {
            $content = "";
        }
        return $content;
    }

    public function getData()
    {
        return $this->data;
    }

    protected function saveData($force=false)
    {
        if (!$force && time() - $this->last_save_time < $this->save_interval) {
            return;
        }

        $result = FS::writeFile($this->data_file, $this->data->getDataString());
        if (!$result) {
            $this->logger->error("Unable to save stat data to file {$this->data_file}");
        }
        $this->last_save_time = time();
    }

    public function shutdown()
    {
        if ($this->process->isLaunched(self::$process_name)) {
            return;
        }
        $this->logger->info('Shutting down...');
        $this->shutdown = true;
    }
}
