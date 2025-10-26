<?php

declare(strict_types=1);

namespace ManaPHP\Db;

use ManaPHP\Di\TypedFactory;

class DbFactory extends TypedFactory implements DbFactoryInterface
{
    public function getType(): string
    {
        return DbInterface::class;
    }
}
