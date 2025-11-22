<?php

declare(strict_types=1);

namespace ManaPHP\Http\Server\Event;

use ManaPHP\Eventing\Attribute\TraceLevel;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::DEBUG)]
class ServerReady
{
    public function __construct(
        public mixed  $server,
        public string $host,
        public int    $port,
        public array  $settings = []
    )
    {

    }
}
