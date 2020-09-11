<?php

namespace Lazy\DelayQueue;

use Lazy\DelayQueue\Contracts\Lock;

class RedisLock implements Lock
{
    /**
     * The name of the lock.
     *
     * @var string
     */
    protected $name;

    /**
     * The number of seconds the lock should be maintained.
     *
     * @var int
     */
    protected $seconds;

    /**
     * The scope identifier of this lock.
     *
     * @var string
     */
    protected $owner;

    /**
     * @var \Redis
     */
    protected $redis;

    /**
     * Create a new lock instance.
     *
     * @param $name
     * @param $seconds
     * @param null $owner
     * @throws \Exception
     */
    public function __construct($name = "lock", $seconds = 60, $owner = null)
    {
        if (is_null($owner)) {
            $owner = base64_encode(random_bytes(16));
        }

        $this->name = $name;
        $this->owner = $owner;
        $this->seconds = $seconds;
        $this->redis = Container::getInstance()->make(RedisProxy::class);
    }

    public function get($callback = null)
    {
        $result = $this->acquire();

        if ($result && is_callable($callback)) {
            try {
                return $callback();
            } finally {
                $this->release();
            }
        }

        return $result;
    }

    public function block($seconds, $callback = null)
    {
        $starting = time();

        while (!$this->acquire()) {
            usleep(250 * 1000);
            if (time() - $seconds >= $starting) {
                throw new \RuntimeException();
            }
        }

        if (is_callable($callback)) {
            try {
                return $callback();
            } finally {
                $this->release();
            }
        }

        return true;
    }

    public function release()
    {
        return (bool)$this->redis->eval(LuaScripts::releaseLock(), [$this->name, $this->owner], 1);
    }

    public function owner()
    {
        return $this->owner;
    }

    public function forceRelease()
    {
        $this->redis->del($this->name);
    }

    /**
     * Attempt to acquire the lock.
     *
     * @return bool
     */
    public function acquire()
    {
        $result = $this->redis->setnx($this->name, $this->owner);

        if ($result === 1 && $this->seconds > 0) {
            $this->redis->expire($this->name, $this->seconds);
        }

        return $result === 1;
    }

    /**
     * Returns the owner value written into the driver for this lock.
     *
     * @return string
     */
    protected function getCurrentOwner()
    {
        return $this->redis->get($this->name);
    }

    /**
     * Determines whether this lock is allowed to release the lock in the driver.
     *
     * @return bool
     */
    protected function isOwnedByCurrentProcess()
    {
        return $this->getCurrentOwner() === $this->owner;
    }
}