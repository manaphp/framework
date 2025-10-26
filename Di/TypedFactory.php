<?php
declare(strict_types=1);

namespace ManaPHP\Di;

use ManaPHP\Di\Attribute\Autowired;

/**
 * @template T
 * @implements FactoryInterface<T>
 */
class TypedFactory implements TypedFactoryInterface
{
    #[Autowired] protected ContainerInterface $container;

    /**
     * @param string $name
     * @return T
     */
    public function get(string $name): object
    {
        return $this->container->get($name);
    }
}