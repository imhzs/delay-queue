<?php

namespace Lazy\DelayQueue\Serializer;

use Lazy\DelayQueue\Contracts\Serializable;

class JsonSerializer implements Serializable
{
    public function serialize($content)
    {
        return json_encode($content, JSON_UNESCAPED_UNICODE);
    }

    public function unserialize($serialized)
    {
        return (array)json_decode($serialized, true);
    }
}