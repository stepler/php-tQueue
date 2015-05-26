<?php
namespace tQueue\Stat;

class Client 
{
    protected $host;

    protected $logger;

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
        @$socket = stream_socket_client($this->host, $errno, $errstr, 2);
        if (!$socket) {
            $this->logger->error("Unable to send statistic: ({$errno}) {$errstr}");
            return false;
        }

        $data = func_get_args();
        $data_to_send = implode("#", $data);

        fwrite($socket, $data_to_send);
        fclose($socket);
        return true;
    }
}
