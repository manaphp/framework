<?php

declare(strict_types=1);

namespace ManaPHP\Redis;

use ManaPHP\Di\FactoriedFactoryInterface;

/**
 * @extends FactoriedFactoryInterface<RedisInterface>
 */
interface RedisFactoryInterface extends FactoriedFactoryInterface
{

}
