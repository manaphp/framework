<?php

declare(strict_types=1);

namespace ManaPHP\Redis;

use ManaPHP\Di\Attribute\Autowired;
use Psr\Container\ContainerInterface;

class RedisFactory implements RedisFactoryInterface
{
    #[Autowired] protected ContainerInterface $container;

    public function get(string $name): RedisInterface
    {
        return $this->container->get(RedisInterface::class . "#$name");
    }
}
