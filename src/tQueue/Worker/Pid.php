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

    protected function getPid($pidfile)
    {
        $content = (int) FS::readFile($pidfile);
        if (empty($content)) {
            throw new \RuntimeException("Unable to get PID from {$pidfile}");
        }
        return $content;
    }

    public function getZombie()
    {
        $result = array();
        $pidnames = array();
        foreach ($this->workers as $worker)
        {
            for ($fork=1; $fork<=$worker->forks; $fork++) {
                $pidnames[] = $this->makePidfileName($worker->name, $fork);
            }
        }

        foreach ($this->pids as $pidfile => $pid)
        {
            if (!in_array(pathinfo($pidfile, PATHINFO_BASENAME), $pidnames)) {
                $result[] = intval($pid);
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
        $pidfile = $this->makePidfileName($worker_name, $fork, true);
        if (!array_key_exists($pidfile, $this->pids)) {
            return 0;
        }
        return $this->pids[$pidfile];
    }

    public function add($pid, $worker_name, $fork)
    {
        $pidfile = $this->makePidfileName($worker_name, $fork, true);
        $result = FS::writeFile($pidfile, $pid);
        if ($result === false) {
            \tQueue\Helper\Tools::killProcess($pid);
            throw new \RuntimeException("Unable to save PID to {$pidfile}");
        }
        $this->pids[$pidfile] = $pid;
    }

    public function remove($pid)
    {
        foreach ($this->pids as $pidfile => $_pid)
        {
            if ($_pid === $pid) {
                FS::deleteFile($pidfile);
                unset($this->pids[$pidfile]);
            }
        }
    }

}
