<?php
namespace tQueue\Statistics;

class Client 
{
    protected $host;

    public function __construct($host)
    {
        $this->host = $host;
    }

    public function send($queue, $worker, $type)
    {
        $socket = stream_socket_client($this->host, $errno, $errstr, 2);
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
