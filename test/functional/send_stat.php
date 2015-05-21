<?php
date_default_timezone_set('GMT');
require __DIR__."/../../vendor/autoload.php";

tQueue::setConfig(__DIR__."/config.ini");

$fp = stream_socket_client("tcp://127.0.0.1:8000", $errno, $errstr, 30);
if (!$fp) {
    echo "$errstr ($errno)<br />\n";
} else {
    fwrite($fp, "some data");
    fclose($fp);
}
?>