<?php
namespace tQueue;

use Psr;
use tQueue\Helper\Validate;

class Logger extends Psr\Log\AbstractLogger  
{
    protected $verbose = false;

    protected $handle;

    public function __construct($config)
    {
        if (!empty($config["verbose"])) {
            $this->verbose = true;
        }

        if (isset($config["file"])) {
            Validate::makefile($config["file"]);
            $this->handle = fopen($config["file"], "a");
        }

        if (empty($this->handle)) {
            $this->handle = STDOUT;
        }
    }

    public function log($level, $message, array $context=array())
    {
        return;
        if (!$this->verbose &&
            ($level == Psr\Log\LogLevel::DEBUG || $level == Psr\Log\LogLevel::INFO)) {
            return;
        }
        fwrite($this->handle,
            '['.$level.'] ['.strftime('%T %Y-%m-%d').'] '.$this->interpolate($message, $context).PHP_EOL);
    }

    public function debug($message, array $context = array()) {
        $this->log(Psr\Log\LogLevel::DEBUG, $message, $context);
    }

    public function info($message, array $context = array()) {
        $this->log(Psr\Log\LogLevel::INFO, $message, $context);
    }

    public function warning($message, array $context = array()) {
        $this->log(Psr\Log\LogLevel::WARNING, $message, $context);
    }

    public function error($message, array $context = array()) {
        $this->log(Psr\Log\LogLevel::ERROR, $message, $context);
    }

    public function interpolate($message, array $context = array())
    {
        $replace = array();
        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = $val;
        }

        return strtr($message, $replace);
    }
}