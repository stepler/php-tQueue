<?php
namespace tQueue\Helper;

class Tools 
{
    public function normalizePath($path)
    {
        return rtrim($path, "\/")
    }

    public function joinPaths()
    {
        $args = func_get_args();
        $paths = array_map(array(self, 'normalizePath'), $args);
        return implode(DIRECTORY_SEPARATOR, $paths);
    }

    public static function killProcess($pid)
    {
        exec("kill -9 {$pid}");
    }
}