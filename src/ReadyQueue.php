<?php

namespace Lazy\DelayQueue;

use Lazy\DelayQueue\Contracts\Queue;
use Lazy\DelayQueue\Contracts\JobInterface;

class ReadyQueue implements Queue
{
    /**
     * @param null $queueName
     * @return int
     */
    public function size($queueName = null): int
    {
        $queueName = $this->getQueueKey();

        return Container::getInstance()->make(RedisProxy::class)->lLen($queueName);
    }

    /**
     * @param JobInterface $job
     * @param null $queueName
     * @return mixed
     * @throws Exceptions\ServiceNotFoundException
     */
    public function push(JobInterface $job, $queueName = null)
    {
        $queueName = $this->getQueueKey();

        return Container::getInstance()->make(RedisProxy::class)->rPush($queueName, $job->getId());
    }

    /**
     * @param string $queueName
     * @return JobInterface
     * @throws Exceptions\ServiceNotFoundException
     */
    public function pop(string $queueName)
    {
        $queueName = $this->getQueueKey();
        $container = Container::getInstance();
        $lock = $container->make(RedisLock::class);

        return Container::getInstance()->make(RedisProxy::class)->lPop($queueName);
    }

    /**
     * @param string $queueName
     * @param $timeout
     * @return mixed
     * @throws Exceptions\ServiceNotFoundException
     */
    public function bpopReadyQueue(string $queueName, $timeout)
    {
        $queueName = $this->getQueueKey();

        return Container::getInstance()->make(RedisProxy::class)->blpop($queueName, $timeout);
    }

    public function getQueueKey()
    {
        return "ready";
    }
}