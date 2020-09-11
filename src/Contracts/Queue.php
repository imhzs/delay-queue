<?php

namespace Lazy\DelayQueue\Contracts;

interface Queue
{
    /**
     * Get the size of the queue.
     *
     * @param  string|null  $queue
     * @return int
     */
    public function size($queue = null): int;

    /**
     * Push a new job onto the queue.
     *
     * @param  JobInterface  $job
     * @param  string|null  $queue
     * @return mixed
     */
    public function push(JobInterface $job, $queue = null);

    /**
     * @param string $queueName
     * @return JobInterface
     */
    public function pop(string $queueName);
}