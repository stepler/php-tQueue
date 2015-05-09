<?php
namespace SMQ;

class MQ 
{
    protected static $instance;

    public static function getInstance()
    {
        $class = get_called_class();
        if(!static::$instance)
            static::$instance = new $class();

        return static::$instance;
    }

    private function __construct(){}

    public static function publish($message, $queues, $options=array())
    {

    }

    public static function consume($queues)
    {

    }
}