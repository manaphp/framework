<?php

declare(strict_types=1);

namespace ManaPHP\Mongodb;

interface MongodbFactoryInterface
{
    public function getInstance(string $name): MongodbInterface;

    public function getDefinitions(): array;

    public function getNames(): array;
}
