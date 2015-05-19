<?php
namespace tQueue\Worker;

use Exception;
use RuntimeException;
use InvalidArgumentException;

class Loader
{
    protected $workers_dir;

    protected $workers_is_loaded = false;
    protected $workers = array();

    public function __construct($workers_dir)
    {
        if (empty($workers_dir) ||
            !file_exists($workers_dir) ||
            !is_dir($workers_dir)) {
            throw new InvalidArgumentException("Invalid workers_dir: {$workers_dir}");
        }
        if (!is_readable($workers_dir)) {
            throw new InvalidArgumentException("Workers dir is not readable");
        }
        $this->workers_dir = $workers_dir;
    }

    protected function loadWorkers()
    {
        $files = $this->getWorkerFiles();
        $classes = get_declared_classes();

        foreach ($files as $file) {
            $this->loadWorkerFile($file);
        }

        $newClasses = array_diff(get_declared_classes(), $classes);

        $this->workers = $this->parseLoadedClasses($newClasses);

        if (empty($this->workers)) {
            throw new Exception("Unable to found workers");
        }
    }

    protected function getWorkerFiles()
    {
        $result = array();

        $Iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->workers_dir));

        $list = new \RegexIterator($Iterator, 
            '/^.+Worker\.php$/', \RecursiveRegexIterator::GET_MATCH);

        foreach ($list as $file) {
            $result = array_merge($result, $file);
        }

        return $result;
    }

    protected function loadWorkerFile($filename)
    {
        $includePathFilename = stream_resolve_include_path($filename);

        if (!$includePathFilename || !is_readable($includePathFilename)) {
            throw new Exception("Cannot open file '{$filename}'.\n");
        }
        include_once $filename;
    }

    protected function parseLoadedClasses($classes)
    {
        $result = array();

        if (empty($classes)) {
            return $result;
        }

        foreach ($classes as $classname) {
            if (preg_match('/^.+_Worker$/', $classname)) {
                $result[] = $classname;
            }
        }

        return $result;
    }

    public function getWorkers()
    {
        if (empty($this->workers)) {
            $this->loadWorkers();
        }

        return $this->workers;
    }
}