<?php
declare(strict_types=1);

namespace ManaPHP\Di;

interface FactoryInterface
{
    public function register(string $type, ContainerInterface $container): void;

    public function getDefinitions(): array;

    public function getNames(): array;
}