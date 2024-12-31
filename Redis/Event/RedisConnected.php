<?php

declare(strict_types=1);

namespace ManaPHP\Redis\Event;

use ManaPHP\Eventing\Attribute\TraceLevel;
use ManaPHP\Redis\Connection;
use Psr\Log\LogLevel;
use Redis;
use RedisCluster;

#[TraceLevel(LogLevel::DEBUG)]
class RedisConnected
{
    public function __construct(
        public Connection $connection,
        public string $uri,
        public Redis|RedisCluster $redis,
    ) {

    }
}
