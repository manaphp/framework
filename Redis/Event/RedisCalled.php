<?php

declare(strict_types=1);

namespace ManaPHP\Redis\Event;

use ManaPHP\Eventing\Attribute\TraceLevel;
use ManaPHP\Redis\Connection;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::DEBUG)]
class RedisCalled
{
    public function __construct(
        public Connection $redis,
        public string     $method,
        public array      $arguments,
        public float      $elapsed,
        public mixed      $return,
    )
    {

    }
}
