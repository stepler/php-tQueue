<?php
namespace tQueue\Stat;

class Server 
{
    protected $socket;

    protected $save_interval = 60;
    protected $last_save_time = 0;

    protected $accept_timeout = 10;

    protected $data;
    protected $data_file;

    protected $shutdown = false;

    public function __construct($config)
    {
        $this->parseConfig($config);
        $this->data = new Data($this->readData());
    }

    protected function parseConfig($config)
    {
        $this->host = $config["host"];
        $this->data_file = $config["data_file"];
        $this->save_interval = $config["save_interval"];
    }

    public function run()
    {
        declare(ticks = 1);
        pcntl_signal(SIGQUIT, array($this, 'shutdown'));

        $this->launch();
    }

    protected function launch()
    {
        $socket = stream_socket_server($this->host, $errno, $errstr);
        if (!$socket) {
            throw new Exception("Unable to create socket server: $errstr ($errno)");
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
        }

        fclose($socket);
    }

    protected function processData($data)
    {
        list($queue, $worker, $type) = explode("#", $data);
        $this->data->inc($queue, $worker, $type);
    }

    protected function readData()
    {
        @$content = file_get_contents($this->data_file);
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

        file_put_contents($this->data_file, $this->data->getDataString());
        $this->last_save_time = time();
    }

    public function shutdown()
    {
        $this->shutdown = true;
    }
}
