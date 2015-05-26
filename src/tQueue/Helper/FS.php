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

    public static function writeFile($filename, $data, $permissions="755")
    {
        $result = false;
        if ((file_exists($filename) && is_writable($filename)) ||
            is_writable(pathinfo($filename, PATHINFO_DIRNAME)))
        {
            $result = file_put_contents($filename, $data);
        }
        return $result !== false;
    }

    public static function deleteFile($filename)
    {
        unlink($filename);
    }
}
