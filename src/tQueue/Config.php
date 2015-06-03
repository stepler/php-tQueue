<?php
namespace tQueue;

class Config implements \ArrayAccess
{
    protected $container;

    public function __construct($filename_or_array)
    {
        $config = $filename_or_array;
        if (is_string($filename_or_array)) {
            $config = $this->loadFromFile($filename_or_array);
        }
        $this->validateConfig($config);
        $this->container = $config;
    }

    protected function loadFromFile($filename)
    {
        $content = \tQueue\Helper\FS::readFile($filename);
        if (empty($content)) {
            throw new \InvalidArgumentException("Invalid config file {$filename}");
        }
        $config = parse_ini_string($content, true);
        if ($config === false) {
            throw new \InvalidArgumentException("Error on parse config from file {$filename}");
        }
        return $config;
    }

    protected function validateConfig($config)
    {
        if (!isset($config["broker"])) {
            throw new \InvalidArgumentException("Unable to found 'broker' section in config");
        }
        if (!isset($config["workers"])) {
            throw new \InvalidArgumentException("Unable to found 'workers' section in config");
        }
        if (!isset($config["logger"])) {
            throw new \InvalidArgumentException("Unable to found 'logger' section in config");
        }
        if (!isset($config["stat"])) {
            throw new \InvalidArgumentException("Unable to found 'stat' section in config");
        }
        if (!isset($config["process"])) {
            throw new \InvalidArgumentException("Unable to found 'process' section in config");
        }
    }

    public function getArray()
    {
        return $this->container;
    }

    public function __get($name)
    {
        return $this->container[$name];
    }

    public function __set($name, $value)
    {
        $this->container[$name] = $value;
    }

    public function __isset($name)
    {
        return isset($this->container[$name]);
    }

    public function __unset($name)
    {
        unset($this->container[$name]);
    }

    public function offsetGet($offset)
    {
        return $this->container[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->container[$offset] = $value;
    }

    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }
}
