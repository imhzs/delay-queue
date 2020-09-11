<?php

namespace Lazy\DelayQueue\Contracts;

interface JobHandler
{
	/**
	 * @param JobInterface $job
	 * @return bool
	 */
    public function handle(JobInterface $job): bool;
}