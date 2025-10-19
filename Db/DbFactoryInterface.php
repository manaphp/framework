<?php

declare(strict_types=1);

namespace ManaPHP\Db;

interface DbFactoryInterface
{
    public function get(string $name): DbInterface;
}
