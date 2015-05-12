<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/unit/test_data/TestBroker.php';

tQueue::setConfig(array(
    "broker" => "TestBroker",
    "broker_settings" => array()
));