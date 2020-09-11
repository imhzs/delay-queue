<?php

namespace Lazy\DelayQueue;

use Lazy\DelayQueue\Serializer\JsonSerializer;

class JobPool
{
    /**
     * @param $jobId
     * @return Contracts\JobInterface|null
     * @throws Exceptions\ServiceNotFoundException
     * @throws \ReflectionException
     */
    public function getJob($jobId)
    {
        $container = Container::getInstance();
        $content = $container->make(RedisProxy::class)->get($jobId);

        if (empty($content)) {
            return null;
        }

        $data = $container->make(JsonSerializer::class)->unserialize($content);

        if (count($data)) {
            return Job::createFromArray($data);
        }

        return null;
    }

    /**
     * @param Job $job
     * @return mixed
     * @throws Exceptions\ServiceNotFoundException
     */
    public function putJob(Job $job)
    {
        $container = Container::getInstance();
        $serializer = $container->make(JsonSerializer::class);
        $content = $serializer->serialize($job->toArray());
        $interval = $serializer->unserialize($job->getInterval());
        $ttl = array_sum($interval) + 15;

        return $container->make(RedisProxy::class)->setex($job->getId(), $ttl, $content);
    }

    /**
     * @param $jobId
     * @return mixed
     * @throws Exceptions\ServiceNotFoundException
     */
    public function removeJob($jobId)
    {
        return Container::getInstance()->make(RedisProxy::class)->del($jobId);
    }
}