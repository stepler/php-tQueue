<?php
namespace tQueue\Broker;

use tQueue\Helper\FS;

class Loader
{
    static public function getBrokerClass($broker_name)
    {
        $ns_broker_class = __NAMESPACE__."\\Storage\\{$broker_name}";

        if (class_exists($ns_broker_class)) {
            return $ns_broker_class;
        }

        $broker_filename = strtolower($broker_name);
        $files = FS::findFiles(FS::joinPaths(__DIR__, "Storage"), "*.php");
        foreach ($files as $file) {
            if ($broker_filename === strtolower(pathinfo($file, PATHINFO_FILENAME)))  {
                require $file;
                break;
            }
        }

        if (!class_exists($ns_broker_class)) {
            throw new \Exception("Unable to find broker '{$broker_name}'");
        }

        return $ns_broker_class;
    }
}
