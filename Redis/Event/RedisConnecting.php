<?php

declare(strict_types=1);

namespace ManaPHP\Redis\Event;

use ManaPHP\Eventing\Attribute\TraceLevel;
use ManaPHP\Redis\Connection;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::INFO)]
class RedisConnecting
{
    public function __construct(
        public Connection $connection,
        public string     $uri,
    )
    {

    }
}
