<?php
declare(strict_types=1);

namespace ManaPHP\Di;

use ManaPHP\Di\Attribute\Autowired;

/**
 * @template T
 * @implements FactoriedFactoryInterface<T>
 */
abstract class FactoriedFactory implements FactoriedFactoryInterface
{
    #[Autowired] protected ContainerInterface $container;

    /**
     * @param string $name
     * @return T
     */
    public function get(string $name): object
    {
        return $this->container->get($this->getType() . "#$name");
    }

    public function getFactory(): FactoryInterface
    {
        return $this->container->get(FactoryInterface::class . '#' . $this->getType());
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