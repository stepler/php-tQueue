<?php
namespace tQueue\Base;

abstract class Manager
{
    protected $tQueue;

    protected $logger;
    protected $process;

    final public function __construct($tQueue, $config=array(), $exclude="")
    {
        $this->tQueue = $tQueue;
        $this->logger = $tQueue->logger;

        if (strpos($exclude, "process") === false) {
            $this->process = $tQueue->process;
        }
        
        $this->parseConfig($config);
        $this->on_construct();
    }

    abstract protected function parseConfig($config);

    protected function on_construct() {}
}
