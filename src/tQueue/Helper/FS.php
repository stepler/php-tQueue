<?php
namespace tQueue\Helper;

class FS 
{
    public static function normalizePath($path)
    {
        return DIRECTORY_SEPARATOR.trim($path, "\/");
    }

    public static function joinPaths()
    {
        $args = func_get_args();
        $paths = array_map(array(__NAMESPACE__."\\FS", "normalizePath"), $args);
        return implode("", $paths);
    }

    public static function findFiles($path, $mask)
    {
        return glob(self::joinPaths($path, $mask));
    }

    public static function readFile($filename)
    {
        if (file_exists($filename) && is_readable($filename)) {
            return file_get_contents($filename);
        }
        return null;
    }

    public static function writeFile($filename, $data, $chmod=0750)
    {
        $result = false;
        if (file_exists($filename)) {
            $result = file_put_contents($filename, $data);
        }
        elseif (is_writable(pathinfo($filename, PATHINFO_DIRNAME))) {
            $result = file_put_contents($filename, $data);
            chmod($filename, $chmod);
        }
        return $result !== false;
    }

    public static function deleteFile($filename)
    {
        unlink($filename);
    }
}
