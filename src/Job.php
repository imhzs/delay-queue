<?php

namespace Lazy\DelayQueue;

use Lazy\DelayQueue\Contracts\Arrayable;
use Lazy\DelayQueue\Contracts\JobInterface;
use Lazy\DelayQueue\Serializer\JsonSerializer;

class Job implements JobInterface, Arrayable
{
    private $id;

    private $topic;

    private $delay;

    private $ttr;

    private $args;

    private $class;

    private $interval;

    private $attempt;

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTopic(): string
    {
        return $this->topic;
    }

    /**
     * @param string $topic
     */
    public function setTopic(string $topic): void
    {
        $this->topic = $topic;
    }

    /**
     * @param int $delay
     */
    public function setDelay(int $delay): void
    {
        $this->delay = $delay;
    }

    /**
     * @return int
     */
    public function getDelay(): int
    {
        return $this->delay;
    }

    /**
     * @param int $ttr
     */
    public function setTTR(int $ttr): void
    {
        $this->ttr = $ttr;
    }

    /**
     * @return int
     */
    public function getTTR(): int
    {
        return $this->ttr;
    }

    /**
     * @param string $class
     */
    public function setClass(string $class): void
    {
        $this->class = $class;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @param string $args
     */
    public function setArgs(string $args): void
    {
        $this->args = $args;
    }

    /**
     * @return string
     */
    public function getArgs(): string
    {
        return $this->args;
    }

    public function setInterval(string $interval): void
    {
        $this->interval = $interval;
    }

    public function getInterval(): string
    {
        return $this->interval;
    }

    public function getDecodedInterval(): array
    {
        return Container::getInstance()->make(JsonSerializer::class)->unserialize($this->getInterval());
    }

    public function setAttempt(int $attempt): void
    {
        $this->attempt = $attempt;
    }

    public function getAttempt(): int
    {
        return $this->attempt;
    }

    /**
     * @param array $data
     * @return JobInterface
     */
    public static function createFromArray(array $data): JobInterface
    {
        $job = new static();

        $job->setId($data["id"]);
        $job->setTopic($data["topic"]);
        $job->setDelay($data["delay"]);
        $job->setClass($data["class"]);
        $job->setTTR($data["ttr"]);
        $job->setArgs($data["args"]);
        $job->setInterval($data["interval"]);
        $job->setAttempt($data["attempt"]);

        return $job;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            "id" => $this->getId(),
            "topic" => $this->getTopic(),
            "delay" => $this->getDelay(),
            "ttr" => $this->getTTR(),
            "class" => $this->getClass(),
            "args" => $this->getArgs(),
            "interval" => $this->getInterval(),
            "attempt" => $this->getAttempt(),
        ];
    }
}