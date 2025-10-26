<?php

declare(strict_types=1);

namespace ManaPHP\Redis;

use ManaPHP\Di\FactoriedFactory;

class RedisFactory extends FactoriedFactory implements RedisFactoryInterface
{
    public function getType(): string
    {
        return RedisInterface::class;
    }
}
