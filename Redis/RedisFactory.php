<?php

declare(strict_types=1);

namespace ManaPHP\Redis;

use ManaPHP\Di\TypedFactory;

class RedisFactory extends TypedFactory implements RedisFactoryInterface
{
    public function getType(): string
    {
        return RedisInterface::class;
    }
}
