<?php
namespace tQueue\Stat;

class Client 
{
    protected $host;

    protected $logger;

    protected $stack = array();

    public function __construct($logger, $config)
    {
        $this->logger = $logger;

        if (empty($config["host"])) {
            throw new \InvalidArgumentException("Unable to found 'host' option in stat config");
        }
        $this->host = $config["host"];
    }

    public function update($queue, $worker, $type)
    {
        $data = func_get_args();
        $this->send("counter", implode("#", $data));
        return true;
    }

    public function clear()
    {
        $this->send("clear", "");
        return true;
    }

    protected function send($type, $message)
    {
        $raw_message = "{$type}@{$message}";
        var_dump($raw_message);
        @$socket = stream_socket_client($this->host, $errno, $errstr, 2);
        if (!$socket) {
            $this->stack[] = $raw_message;
            $this->logger->error("Unable to send statistic: ({$errno}) {$errstr}");
            return false;
        }

        if (!empty($this->stack)) {
            foreach ($this->stack as $stack_data) {
                fwrite($socket, $raw_message);
            }
            $this->stack = array();
        }

        fwrite($socket, $raw_message);
        fclose($socket);
    }
}
