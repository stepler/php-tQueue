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

    public function send($queue, $worker, $type)
    {
        $data = func_get_args();
        $data_to_send = implode("#", $data);

        @$socket = stream_socket_client($this->host, $errno, $errstr, 2);
        if (!$socket) {
            $this->stack[] = $data_to_send;
            $this->logger->error("Unable to send statistic: ({$errno}) {$errstr}");
            return false;
        }

        if (!empty($this->stack)) {
            foreach ($this->stack as $stack_data) {
                fwrite($socket, $data_to_send);
            }
            $this->stack = array();
        }

        fwrite($socket, $data_to_send);
        fclose($socket);
        return true;
    }
}
