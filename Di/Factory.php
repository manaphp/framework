<?php
declare(strict_types=1);

namespace ManaPHP\Di;

class Factory implements FactoryInterface
{
    public function __construct(public array $definitions = [])
    {

    }

    public function register(string $type, ContainerInterface $container): void
    {
        $container->set($type, '#default');

        foreach ($this->definitions as $name => $definition) {
            $container->set("$type#$name", $definition);
        }
    }

    public function getDefinitions(): array
    {
        return $this->definitions;
    }
}