<?php

declare(strict_types=1);

namespace ManaPHP\Http\Server\Event;

use JsonSerializable;
use ManaPHP\Eventing\Attribute\TraceLevel;
use Psr\Log\LogLevel;
use Swoole\Http\Server;

#[TraceLevel(LogLevel::NOTICE)]
class ServerStart implements JsonSerializable
{
    public function __construct(public Server $server)
    {

    }

    public function jsonSerialize(): array
    {
        return [
            'host'     => $this->server->host,
            'port'     => $this->server->port,
            'mode'     => $this->server->mode,
            'settings' => $this->server->setting,
        ];
    }
}
