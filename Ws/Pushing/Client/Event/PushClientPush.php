<?php

declare(strict_types=1);

namespace ManaPHP\Ws\Pushing\Client\Event;

use ManaPHP\Eventing\Attribute\TraceLevel;
use ManaPHP\Ws\Pushing\ClientInterface;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::NOTICE)]
class PushClientPush
{
    public function __construct(
        public ClientInterface $client,
        public string          $type,
        public string          $receivers,
        public string          $message,
        public string          $endpoint,
    )
    {

    }
}
