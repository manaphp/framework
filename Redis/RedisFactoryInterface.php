<?php

declare(strict_types=1);

namespace ManaPHP\Redis;

interface RedisFactoryInterface
{
    public function getInstance(string $name): RedisInterface;

    public function getDefinitions(): array;

    public function getNames(): array;
}
