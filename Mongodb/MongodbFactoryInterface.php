<?php

declare(strict_types=1);

namespace ManaPHP\Mongodb;

interface MongodbFactoryInterface
{
    public function get(string $name): MongodbInterface;
}
