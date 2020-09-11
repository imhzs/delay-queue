<?php

namespace Lazy\DelayQueue;

class Bucket
{
    /**
     * @param string $jobId
     * @param int $delay
     * @return mixed
     * @throws Exceptions\ServiceNotFoundException
     */
    public function pushBucket(string $jobId, int $delay)
    {
        $bucketName = $this->generateBucketName();

        return Container::getInstance()->make(RedisProxy::class)->zAdd($bucketName, $delay, $jobId);
    }

    /**
     * @param $index
     * @return mixed
     * @throws Exceptions\ServiceNotFoundException
     */
    public function getJobsMinDelayTime($index)
    {
        $bucketName = $this->generateBucketName();

        return Container::getInstance()->make(RedisProxy::class)->zRange($bucketName, 0, $index - 1, true);
    }

    /**
     * @param string $jobId
     * @return mixed
     * @throws Exceptions\ServiceNotFoundException
     */
    public function  removeBucket(string $jobId)
    {
        $bucketName = $this->generateBucketName();

        return Container::getInstance()->make(RedisProxy::class)->zRem($bucketName, $jobId);
    }

    /**
     * 获取bucket
     * @return string
     */
    public function generateBucketName()
    {
        return 'bucket';
    }
}