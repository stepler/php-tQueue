<?php
namespace tQueue\Worker;

use tQueue\Helper\Validate;
use tQueue\Helper\FS;

class Pid 
{
    protected $workers;

    protected $pids;

    protected $path;

    public function __construct($path)
    {
        Validate::directory($path);
        $this->path = $path;
        $this->pids = $this->getRegisteredPids();
    }

    public function setWorkers($list)
    {
        $this->workers = $list;
    }

    protected function makePidfileName($name, $fork, $get_full_path=false)
    {
        $name = sprintf("%s.%s.pid", $name, $fork);
        return $get_full_path ? FS::joinPaths($this->path, $name) : $name ;
    }

    protected function getRegisteredPids()
    {
        $result = array();
        $exist_pidfiles = FS::findFiles($this->path, "*.pid");
        foreach ($exist_pidfiles as $pidfile) {
            $result[$pidfile] = $this->getPid($pidfile);
        }
        return $result;
    }

    protected function getPid($filename)
    {
        $content = FS::readFile($filename);
        if (empty($content)) {
            throw new \RuntimeException("Unable to get PID from {$filename}");
        }
        return $content;
    }

    public function getZombiePids()
    {
        $result = array();
        $pidnames = array();
        foreach ($this->workers as $worker)
        {
            for ($fork=1; $fork<=$worker["forks"]; $fork++) {
                $pidnames[] = $this->makePidfileName($worker["name"], $fork);
            }
        }

        foreach ($this->pids as $pidfile => $pid)
        {
            if (in_array(pathinfo($pidfile, PATHINFO_BASENAME), $pidnames)) {
                $result[] = $pid;
            }
        }

        return $result;
    }

    public function getAll()
    {
        return array_values($this->pids);
    }

    public function getByWorker($worker_name, $fork=1)
    {
        $filename = $this->makePidfileName($worker_name, $fork, true);
        if (!array_key_exists($filename, $this->pids)) {
            return 0;
        }
        return $this->pids[$filename];
    }

    public function add($pid, $worker_name, $fork)
    {
        $filename = $this->makePidfileName($worker_name, $fork, true);
        $result = FS::writeFile($filename, $pid);
        if ($result === false) {
            \tQueue\Helper\Tools::killProcess($pid);
            throw new \RuntimeException("Unable to save PID to {$filename}");
        }
        $this->pids[$filename] = $pid;
    }

    public function remove($pid)
    {
        foreach ($this->pids as $filename => $_pid)
        {
            if ($_pid === $pid) {
                FS::deleteFile($filename);
                unset($this->pids[$filename]);
            }
        }
    }

}
