<?php
namespace tQueue\Worker;

use tQueue\Helper\Validate;

class Loader
{
    protected $workers_dir;

    protected $workers_is_loaded = false;
 
    protected $workers = array();

    public function __construct($workers_dir)
    {
        Validate::directory($workers_dir);
        $this->workers_dir = $workers_dir;
    }

    protected function loadWorkers()
    {
        $classes = get_declared_classes();
        $this->loadWorkerFiles();
        $new_classes = array_diff(get_declared_classes(), $classes);

        $this->workers = $this->parseLoadedClasses($new_classes);
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

    protected function loadWorkerFiles()
    {
        $files = $this->getWorkerFiles();
        foreach ($files as $file) {
            $includePathFilename = stream_resolve_include_path($file);
            if (!$includePathFilename || !is_readable($includePathFilename)) {
                throw new \RuntimeException("Cannot open file '{$filename}'.\n");
            }
            include_once $file;
        }
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
        if ($this->workers_is_loaded === false) {
            $this->loadWorkers();
            $this->workers_is_loaded = true;
        }

        return $this->workers;
    }
}