<?php

namespace Lazy\DelayQueue\Process;

use Lazy\DelayQueue\Contracts\JobHandler;
use Lazy\DelayQueue\Contracts\JobInterface;

abstract class AbstractJob implements JobHandler
{
	/**
	 * @param JobInterface $job
	 * @return bool
	 */
	public function perform(JobInterface $job): bool
	{
		return $this->handle($job);
	}
}