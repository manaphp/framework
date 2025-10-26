<?php

declare(strict_types=1);

namespace ManaPHP\Db;

use ManaPHP\Di\FactoriedFactory;

class DbFactory extends FactoriedFactory implements DbFactoryInterface
{
    public function getType(): string
    {
        return DbInterface::class;
    }
}
