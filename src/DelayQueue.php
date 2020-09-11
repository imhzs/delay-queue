<?php

namespace Lazy\DelayQueue;

use Monolog\Logger;
use Lazy\DelayQueue\Contracts\Queue;
use Lazy\DelayQueue\Contracts\JobInterface;

class DelayQueue implements Queue
{
    /**
     * @param null $queue
     * @return int
     */
    public function size($queueName = null): int
    {
        return Container::getInstance()->make(RedisProxy::class)->zCard($queueName);
    }

    /**
     * @param JobInterface $job
     * @param null $queue
     * @return bool|mixed
     * @throws Exceptions\ServiceNotFoundException
     */
    public function push(JobInterface $job, $queue = null)
    {
        $container = Container::getInstance();
        $logger = $container->make(Logger::class);

        if (!$container->make(JobPool::class)->putJob($job)) {
            $logger->info("Put job pool failed: ", $job->toArray());
            return false;
        }

        $delay = time() + $job->getDelay();
        $result = $container->make(Bucket::class)->pushBucket($job->getId(), $delay);
        $logger->info("Put to bucket result: " . (int)$result, $job->toArray());

        // Bucket添加失败 删除元数据
        if (!$result) {
            $container->make(JobPool::class)->removeJob($job->getId());
            return false;
        }

        return $job->getId();
    }

    /**
     * @param $jobId
     * @return mixed
     * @throws Exceptions\ServiceNotFoundException
     */
    public function remove($jobId)
    {
        $container = Container::getInstance();
        $container->make(Bucket::class)->removeBucket($jobId);

        return $container->make(JobPool::class)->removeJob($jobId);
    }

    /**
     * @param $jobId
     * @return JobInterface
     * @throws Exceptions\ServiceNotFoundException
     */
    public function get($jobId): ?JobInterface
    {
        return Container::getInstance()->make(JobPool::class)->getJob($jobId);
    }

    /**
     * @param string $queueName
     * @return JobInterface
     * @throws Exceptions\ServiceNotFoundException
     */
    public function pop(string $queueName)
    {
        $container = Container::getInstance();
        $readyJob = $container->make(ReadyQueue::class)->pop($queueName);
        $job = $this->get($readyJob);
        $delay = time() + $job->getTTR();
        $container->make(Bucket::class)->pushBucket($job->getId(), $delay);

        return $job;
    }
}