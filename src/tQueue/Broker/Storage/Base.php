<?php
namespace tQueue\Broker\Storage;

abstract class Base 
{
    public function __construct($config)
    {
        $this->parseConfig($config);
    }

    abstract protected function parseConfig($config);

    abstract public function find($queue, $status);

    abstract public function create($id, $queue, $status, $data);

    abstract public function update($id, $queue, $status);
}
