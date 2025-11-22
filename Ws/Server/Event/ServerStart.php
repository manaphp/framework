<?php

declare(strict_types=1);

namespace ManaPHP\Ws\Server\Event;

use ManaPHP\Eventing\Attribute\TraceLevel;
use ManaPHP\Ws\ServerInterface;
use Psr\Log\LogLevel;
use Swoole\Http\Server;

#[TraceLevel(LogLevel::NOTICE)]
class ServerStart
{
    public function __construct(
        public ServerInterface $server,
        public Server          $swoole,
        public int             $worker_id,
    )
    {

    }
}
