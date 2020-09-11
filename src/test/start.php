<?php

include realpath(__DIR__ . '/../../vendor/autoload.php');

$worker = new \Lazy\DelayQueue\Worker();

$worker->setWorkerNum(2);
$container = $worker->getContainer();

\Lazy\DelayQueue\RedisProxy::setHost("127.0.0.1");
\Lazy\DelayQueue\RedisProxy::setPort(6379);

$worker->run();