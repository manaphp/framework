<?php

declare(strict_types=1);

namespace ManaPHP\Http\Server\Event;

use ManaPHP\Eventing\Attribute\TraceLevel;
use Psr\Log\LogLevel;
use Swoole\Http\Server;

#[TraceLevel(LogLevel::DEBUG)]
class ServerTaskerStop
{
    public function __construct(public Server $server, public int $worker_id, public int $tasker_id)
    {

    }
}
