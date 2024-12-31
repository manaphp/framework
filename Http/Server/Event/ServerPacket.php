<?php

declare(strict_types=1);

namespace ManaPHP\Http\Server\Event;

use ManaPHP\Eventing\Attribute\TraceLevel;
use Psr\Log\LogLevel;
use Swoole\Http\Server;

#[TraceLevel(LogLevel::INFO)]
class ServerPacket
{
    public function __construct(public Server $server, public string $data, public array $client)
    {

    }
}
