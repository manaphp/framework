<?php

declare(strict_types=1);

namespace ManaPHP\Db;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\FactoryInterface;
use Psr\Container\ContainerInterface;

class DbFactory implements DbFactoryInterface
{
    #[Autowired] protected ContainerInterface $container;

    public function getInstance(string $name): DbInterface
    {
        return $this->container->get(DbInterface::class . "#$name");
    }

    public function getFactory(): FactoryInterface
    {
        return $this->container->get(FactoryInterface::class . '#' . DbInterface::class);
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
