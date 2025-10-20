<?php

declare(strict_types=1);

namespace ManaPHP\Redis;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\FactoryInterface;
use Psr\Container\ContainerInterface;

class RedisFactory implements RedisFactoryInterface
{
    #[Autowired] protected ContainerInterface $container;

    public function getInstance(string $name): RedisInterface
    {
        return $this->container->get(RedisInterface::class . "#$name");
    }

    public function getFactory(): FactoryInterface
    {
        return $this->container->get(FactoryInterface::class . '#' . RedisInterface::class);
    }

    public function getDefinitions(): array
    {
        return $this->getFactory()->getDefinitions();
    }

    public function getNames(): array
    {
        return $this->getFactory()->getNames();
    }
}
