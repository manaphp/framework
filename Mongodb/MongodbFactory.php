<?php

declare(strict_types=1);

namespace ManaPHP\Mongodb;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\FactoryInterface;
use Psr\Container\ContainerInterface;

class MongodbFactory implements MongodbFactoryInterface
{
    #[Autowired] protected ContainerInterface $container;

    public function getInstance(string $name): MongodbInterface
    {
        return $this->container->get(MongodbInterface::class . "#$name");
    }

    public function getFactory(): FactoryInterface
    {
        return $this->container->get(FactoryInterface::class . '#' . MongodbInterface::class);
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
