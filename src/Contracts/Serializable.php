<?php

namespace Lazy\DelayQueue\Contracts;

interface Serializable
{
    public function serialize($content);

    public function unserialize($serialized);
}