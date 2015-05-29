<?php
namespace tQueue\Helper;

class Tools 
{
    public static function killProcess($pid)
    {
        exec("kill -9 {$pid}");
    }

    public static function statusProcess($pid)
    {
        exec("ps -o pid,state -p {$pid}", $output, $returnCode);
        return $returnCode === 0;
    }
}