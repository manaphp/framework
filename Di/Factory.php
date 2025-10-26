<?php
declare(strict_types=1);

namespace ManaPHP\Di;

use JsonSerializable;

class Factory implements FactoryInterface, JsonSerializable
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

    public function getNames(): array
    {
        return array_keys($this->definitions);
    }

    public function jsonSerialize(): array
    {
        return ['definitions' => $this->definitions];
    }
}