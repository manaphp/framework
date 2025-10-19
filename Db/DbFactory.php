<?php

declare(strict_types=1);

namespace ManaPHP\Db;

use ManaPHP\Di\Attribute\Autowired;
use Psr\Container\ContainerInterface;

class DbFactory implements DbFactoryInterface
{
    #[Autowired] protected ContainerInterface $container;

    public function get(string $name): DbInterface
    {
        return $this->container->get(DbInterface::class . "#$name");
    }
}
