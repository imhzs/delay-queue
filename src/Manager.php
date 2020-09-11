<?php

namespace Lazy\DelayQueue;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Lazy\DelayQueue\Process\AbstractJob;
use Monolog\Processor\PsrLogMessageProcessor;
use Lazy\DelayQueue\Serializer\JsonSerializer;

class Manager
{
    /**
     * @throws Exceptions\ServiceNotFoundException
     * @throws \ReflectionException
     */
    public function __construct()
    {
        $this->initialize();
    }

    /**
     * @return DelayQueue
     * @throws \ReflectionException
     */
    public function delayQueue(): DelayQueue
    {
        return Container::getInstance()->make(DelayQueue::class);
    }

    /**
     * @throws \ReflectionException
     */
    public function handleReadyQueue(): void
    {
        $container = Container::getInstance();
        $readyQueue = $container->make(ReadyQueue::class);
        $logger = $container->make(Logger::class);

        if ($readyQueue->size() <= 0) {
            return;
        }

        $jobId = $readyQueue->pop("");
        $job = $container->make(JobPool::class)->getJob($jobId);

        if ($job === null) {
            return;
        }

        $class = $job->getClass();

        if (!class_exists($class)) {
            throw new \RuntimeException("Class: [{$class}] not found");
        }

        if (!is_subClass_of($class, AbstractJob::class)) {
            throw new \RuntimeException(sprintf('[%s] is not subclass of [%s]', $class, AbstractJob::class));
        }

        $delayQueue = $container->make(DelayQueue::class);
        $handler = $container->make($class);
        $attempt = $job->getAttempt() + 1;
        $interval = $job->getDecodedInterval();

        try {
            if ($handler->perform($job)) {
                $delayQueue->remove($job->getId());
                return;
            }
        } catch (\Throwable $e) {
            $logger->error(sprintf("Job execution failed %s\r\n%s", $e->getMessage(), $e->getTraceAsString()));
        }

        $logger->info("Interval: ", $interval);

        if (!isset($interval[$attempt])) {
            $delayQueue->remove($job->getId());
            $logger->error(sprintf("Job: %s hit max attempt %s", $job->getId(), $attempt));
            return;
        }

        $job->setAttempt($attempt);
        $job->setDelay($interval[$attempt]);

        $delayQueue->remove($job->getId());
        $delayQueue->push($job);
    }

    /**
     * @throws Exceptions\ServiceNotFoundException
     * @throws \ReflectionException
     */
    protected function initialize()
    {
        $container = Container::getInstance();

        // 注册Serializer
        $container->singleton(JsonSerializer::class);

        // 注册JobPool
        $container->singleton(JobPool::class);

        // 注册Bucket
        $container->singleton(Bucket::class);

        // 注册ReadyQueue
        $container->singleton(ReadyQueue::class);

        // 注册DelayQueue
        $container->singleton(DelayQueue::class);

        // 注册RedisLock
        $container->singleton(RedisLock::class);

        // 注册logger
        $logger = new Logger('delay-queue');
        $logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG, true, null, true));
        $logger->pushProcessor(new PsrLogMessageProcessor());
        $container->instance(Logger::class, $logger);
    }
}