<?php

namespace Lazy\DelayQueue\Contracts;

interface Arrayable
{
    public function toArray(): array;
}