<?php

declare(strict_types=1);

namespace ManaPHP\Mongodb\Event;

use ManaPHP\Eventing\Attribute\TraceLevel;
use ManaPHP\Mongodb\Connection;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::INFO)]
class MongodbConnect extends AbstractEvent
{
    public function __construct(
        public Connection $connection,
        public string $uri,
    ) {

    }
}
