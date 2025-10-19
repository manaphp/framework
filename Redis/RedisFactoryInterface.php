<?php

declare(strict_types=1);

namespace ManaPHP\Redis;

interface RedisFactoryInterface
{
    public function get(string $name): RedisInterface;
}
