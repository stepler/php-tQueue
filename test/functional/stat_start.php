<?php
date_default_timezone_set('GMT');
require __DIR__."/../../vendor/autoload.php";

tQueue::setConfig(__DIR__."/config.ini");
tQueue::startStatServer();
