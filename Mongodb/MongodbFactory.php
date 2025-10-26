<?php

declare(strict_types=1);

namespace ManaPHP\Mongodb;

use ManaPHP\Di\FactoriedFactory;

class MongodbFactory extends FactoriedFactory implements MongodbFactoryInterface
{
    public function getType(): string
    {
        return MongodbInterface::class;
    }
}
