<?php

declare(strict_types=1);

namespace ManaPHP\Mongodb;

use ManaPHP\Di\Attribute\Autowired;
use Psr\Container\ContainerInterface;

class MongodbFactory implements MongodbFactoryInterface
{
    #[Autowired] protected ContainerInterface $container;

    public function get(string $name): MongodbInterface
    {
        return $this->container->get(MongodbInterface::class . "#$name");
    }
}
