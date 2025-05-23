<?php

declare(strict_types=1);

namespace ManaPHP\Ws\Chatting\Client\Event;

use ManaPHP\Eventing\Attribute\TraceLevel;
use ManaPHP\Ws\Chatting\ClientInterface;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::INFO)]
class ChatClientPush
{
    public function __construct(
        public ClientInterface $client,
        public string $type,
        public string $room,
        public string|array $receivers,
        public string|array $message,
    ) {

    }
}
