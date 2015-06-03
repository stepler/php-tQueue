<?php
namespace tQueue\Process;

use tQueue\Helper\FS;
use tQueue\Process\Manager;

class Process
{
    const STATUS_LAUNCHING = 'launching';
    const STATUS_LAUNCHED = 'launched';
    const STATUS_STOPPING = 'stopping';
    const STATUS_STOPPED = 'stopped';

    protected $group;
    protected $name;
    protected $status;
    protected $pid;

    protected $origin;

    static protected $path;

    public static function setPath($path)
    {
        self::$path = FS::normalizePath($path);
    }

    public static function getProcessByGroup($group)
    {
        $list = array();
        $files = FS::findFiles(self::$path, "{$group}.*.*.pid");
        foreach ($files as $file) {
            $list[] = new self(null, $file);
        }
        return $list;
    }

    public static function getAllProcess()
    {
        $list = array();
        $files = FS::findFiles(self::$path, "*.*.*.pid");
        foreach ($files as $file) {
            $list[] = new self(null, $file);
        }
        return $list;
    }

    public function __construct($name, $file=null)
    {
        if (empty($file)) {
            $file = FS::findFile(self::$path, "*.{$name}.*.pid");
        }
        $this->setInfo($name, $file);
    }

    protected function setInfo($name, $file)
    {
        if (empty($name) && empty($file)) {
            throw new \RuntimeException("...");
        }

        if (empty($file)) {
            $this->status = self::STATUS_STOPPED;
            $this->name = $name;
            $this->group = "default";
        }
        else {
            list($group, $name, $status) = explode(".", pathinfo($file, PATHINFO_FILENAME));
            if (empty($name) || empty($group) || empty($status)) {
                throw new \RuntimeException("...");
            }
            $this->origin = $file;
            $this->status = $status;
            $this->name = $name;
            $this->group = $group;
        }
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setRealStatus()
    {
        $status = false;
        $pid = $this->getPid();
        if ($pid) {
            $status = Manager::statusProcess($pid);
        }

        if ($status === false && $this->status !== self::STATUS_STOPPED) {
            $this->setStatus(self::STATUS_STOPPED);
            $this->setPid(null);
            return;
        }

        return $this->status;
    }

    public function setStatus($status)
    {
        if (!in_array($status, array(self::STATUS_LAUNCHING, 
            self::STATUS_LAUNCHED, self::STATUS_STOPPING, self::STATUS_STOPPED))) {
            throw new \InvalidArgumentException("Invalid process status - '{$status}'");
        }
        $this->status = $status;
        $this->save();
    }

    public function getName()
    {
        return $this->name;
    }

    public function getGroup()
    {
        return $this->group;
    }

    public function setGroup($group)
    {
        $this->group = $group;
        $this->save();
    }

    public function setPid($pid)
    {
        $this->pid = $pid;
        $this->saveContent();
    }

    public function getPid()
    {
        $this->pid = $this->readContent();
        return $this->pid;
    }

    public function parseConfig($config)
    {
        if (empty($config["path"])) {
            throw new \InvalidArgumentException("Unable to found 'path' option in process config");
        }

        Validate::directory($config["path"]);
        $this->path = $config["path"];
    }

    protected function save()
    {
        if (empty($this->origin)) {
            return FS::writeFile($this->getFilePath(), null);
        }
        else {
            return FS::renameFile($this->origin, $this->getFilePath());
        }
        $this->origin = $this->getFilePath();
    }

    protected function saveContent()
    {
        return FS::writeFile($this->getFilePath(), $this->pid);
    }

    protected function readContent()
    {
        return FS::readFile($this->getFilePath(), $this->pid);
    }

    protected function getFilePath()
    {
        return FS::joinPaths(self::$path, "{$this->group}.{$this->name}.{$this->status}.pid");
    }
}
