<?php

declare(strict_types=1);

namespace ManaPHP\Ws\Chatting\Server\Event;

use ManaPHP\Eventing\Attribute\TraceLevel;
use ManaPHP\Ws\Chatting\ServerInterface;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::NOTICE)]
class ServerPushing
{
    public function __construct(
        public ServerInterface $server,
        public string $type,
        public array $receivers,
        public string $message,
    ) {

    }
}
