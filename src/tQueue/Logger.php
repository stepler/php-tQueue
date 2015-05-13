<?php
namespace tQueue;
use Psr;

class Logger extends Psr\Log\AbstractLogger  
{
    protected static $verbose = false;

    public static function setVerbose($value)
    {
        self::$verbose = !!$value;
    }

    public function log($level, $message, array $context = array())
    {
        if (self::$verbose) {
            fwrite(
                STDOUT,
                '[' . $level . '] [' . strftime('%T %Y-%m-%d') . '] ' . $this->interpolate($message, $context) . PHP_EOL
            );
            return;
        }

        if (!($level === Psr\Log\LogLevel::INFO || $level === Psr\Log\LogLevel::DEBUG)) {
            fwrite(
                STDOUT,
                '[' . $level . '] ' . $this->interpolate($message, $context) . PHP_EOL
            );
        }
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