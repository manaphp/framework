<?php

declare(strict_types=1);

namespace ManaPHP\Mongodb;

use ManaPHP\Di\TypedFactory;

class MongodbFactory extends TypedFactory implements MongodbFactoryInterface
{
    public function getType(): string
    {
        return MongodbInterface::class;
    }
}
