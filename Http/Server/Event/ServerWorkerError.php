<?php

declare(strict_types=1);

namespace ManaPHP\Http\Server\Event;

use ManaPHP\Eventing\Attribute\TraceLevel;
use Psr\Log\LogLevel;
use Swoole\Http\Server;

#[TraceLevel(LogLevel::NOTICE)]
class ServerWorkerError
{
    public function __construct(
        public Server $server,
        public int $worker_id,
        public int $worker_pid,
        public int $exit_code,
        public int $signal
    ) {

    }
}
