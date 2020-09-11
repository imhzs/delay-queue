<?php

namespace Lazy\DelayQueue\Process;

use Monolog\Logger;
use Lazy\DelayQueue\Bucket;
use Lazy\DelayQueue\JobPool;
use Lazy\DelayQueue\Container;
use Lazy\DelayQueue\RedisLock;
use Lazy\DelayQueue\ReadyQueue;
use Lazy\DelayQueue\Contracts\JobInterface;

class Timer
{
    private static $num = 50;

    /**
     * @throws \Lazy\DelayQueue\Exceptions\ServiceNotFoundException
     * @throws \ReflectionException
     */
    public static function tick()
    {
        self::release();
    }

    /**
     * @return void
     * @throws \Lazy\DelayQueue\Exceptions\ServiceNotFoundException
     * @throws \ReflectionException
     */
    protected static function release()
    {
        $container = Container::getInstance();
        $logger = $container->make(Logger::class);

        $bucketJobs = $container->make(Bucket::class)->getJobsMinDelayTime(self::$num);

        if (empty($bucketJobs)) {
            return;
        }

        foreach ($bucketJobs as $jobId => $time) {
            if ($time > time()) {
                break;
            }

            $job = $container->make(JobPool::class)->getJob($jobId);

            // job元信息不存在, 从bucket中删除
            if (!$job instanceof JobInterface) {
                $container->make(Bucket::class)->removeBucket($jobId);
                continue;
            }

            // 元信息中delay是否小于等于当前时间
            if ($job->getDelay() > time()) {
                $container->make(Bucket::class)->removeBucket($jobId);
                $container->make(Bucket::class)->pushBucket($jobId, $job->getDelay());
                continue;
            }

            $logger->info("Push to ready queue: ", $job->toArray());
            $container->make(Bucket::class)->removeBucket($job->getId());
            $result = $container->make(ReadyQueue::class)->push($job, $job->getTopic());
            $logger->info("Push to ready queue result: " . (int)$result);
        }
    }
}