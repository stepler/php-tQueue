<?php
namespace tQueue\Base;

abstract class Manager
{
    protected $tQueue;

    final public function __construct($tQueue, $config=array())
    {
        $this->tQueue = $tQueue;
        $this->parseConfig($config);
        $this->on_construct();
    }

    abstract protected function parseConfig($config);

    protected function on_construct() {}
}
