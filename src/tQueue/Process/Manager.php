<?php
namespace tQueue\Process;

use tQueue\Process\Process;
use tQueue\Helper\Validate;

class Manager extends \tQueue\Base\Manager
{
    protected $path;

    public function parseConfig($config)
    {
        if (empty($config["path"])) {
            throw new \InvalidArgumentException("Unable to found 'path' option in process config");
        }

        Validate::directory($config["path"]);
        Process::setPath($config["path"]);
    }

    protected function getProcess($name)
    {
        return new Process($name);
    }

    public function isLaunching($name)
    {
        $process = $this->getProcess($name);
        return $process->getStatus($name) === Process::STATUS_LAUNCHING;
    }

    public function isLaunched($name)
    {
        $process = $this->getProcess($name);
        return $process->getStatus($name) === Process::STATUS_LAUNCHED;
    }

    public function isStopping($name)
    {
        $process = $this->getProcess($name);
        return $process->getStatus($name) === Process::STATUS_STOPPING;
    }

    public function isStopped($name)
    {
        $process = $this->getProcess($name);
        return $process->getStatus($name) === Process::STATUS_STOPPED;
    }

    public function setLaunching($name)
    {
        $process = $this->getProcess($name);
        $process->setStatus(Process::STATUS_LAUNCHING);
    }

    public function setLaunched($name)
    {
        $process = $this->getProcess($name);
        $process->setStatus(Process::STATUS_LAUNCHED);
    }

    public function setStopping($name, $group=null)
    {
        $list = empty($group)
                 ? array($this->getProcess($name))
                 : Process::getProcessByGroup($group);

        foreach ($list as $process) {
            $process->setStatus(Process::STATUS_STOPPING);
        }
    }

    public function setStoped($name, $clear_pid=true)
    {
        $process = $this->getProcess($name);
        $process->setStatus(Process::STATUS_STOPPED);
        if ($clear_pid === true) {
            $process->setPid(null, $name);
        }
    }

    public function setPid($pid, $name)
    {
        $process = $this->getProcess($name);
        $process->setPid($pid);
    }

    public function setGroup($name, $group)
    {
        $process = $this->getProcess($name);
        $process->setGroup($group);
    }

    public static function statusProcess($pid)
    {
        exec("ps -o pid,state -p {$pid}", $output, $returnCode);
        return $returnCode === 0;
    }

    public static function terminateProcess($pid)
    {
        exec("kill -15 {$pid}");
    }

    public static function killProcess($pid)
    {
        exec("kill -9 {$pid}");
    }

    public function status()
    {
        $result = array();
        $list = Process::getAllProcess();
        foreach ($list as $process) {
            $process->setRealStatus();
            $result[] = array(
                "group" => $process->getGroup(),
                "name" => $process->getName(),
                "status" => $process->getStatus()
            );
        }
        return $result;
    }
}
