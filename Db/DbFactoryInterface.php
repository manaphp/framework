<?php

declare(strict_types=1);

namespace ManaPHP\Db;

interface DbFactoryInterface
{
    public function getInstance(string $name): DbInterface;

    public function getDefinitions(): array;

    public function getNames(): array;
}
