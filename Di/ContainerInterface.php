<?php

declare(strict_types=1);

namespace ManaPHP\Di;

use Psr\Container\ContainerInterface as PSrContainerInterface;

interface ContainerInterface extends PSrContainerInterface, MakerInterface, InvokerInterface
{
    public function set(string $id, mixed $definition): static;

    public function remove(string $id): static;

    public function getDefinition(string $id): mixed;

    public function getInstances(): array;
}
