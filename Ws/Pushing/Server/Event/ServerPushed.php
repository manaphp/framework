<?php

declare(strict_types=1);

namespace ManaPHP\Ws\Pushing\Server\Event;

use ManaPHP\Eventing\Attribute\TraceLevel;
use ManaPHP\Ws\Pushing\ServerInterface;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::DEBUG)]
class ServerPushed extends AbstractEvent
{
    public function __construct(
        public ServerInterface $server,
        public string $type,
        public array $receivers,
        public string $message,
    ) {

    }
}
