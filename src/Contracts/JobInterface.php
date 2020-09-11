<?php

namespace Lazy\DelayQueue\Contracts;

interface JobInterface
{
    public function setId(string $id): void;

    public function getId(): string;

    public function getTopic(): string ;

    public function setTopic(string $topic): void;

    public function setDelay(int $delay): void;

    public function getDelay(): int;

    public function setTTR(int $ttr): void;

    public function getTTR(): int;

    public function setClass(string $class): void;

    public function getClass(): string ;

    public function setArgs(string $args): void;

    public function getArgs(): string;

    public function setInterval(string $interval): void;

    public function getInterval(): string;

    public function setAttempt(int $attempt): void;

    public function getAttempt(): int;
}