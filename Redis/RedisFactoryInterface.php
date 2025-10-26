<?php

declare(strict_types=1);

namespace ManaPHP\Redis;

use ManaPHP\Di\TypedFactoryInterface;

/**
 * @extends TypedFactoryInterface<RedisInterface>
 */
interface RedisFactoryInterface extends TypedFactoryInterface
{

}
