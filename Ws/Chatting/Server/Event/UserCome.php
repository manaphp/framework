<?php

declare(strict_types=1);

namespace ManaPHP\Ws\Chatting\Server\Event;

use ManaPHP\Eventing\Attribute\TraceLevel;
use ManaPHP\Ws\Chatting\ServerInterface;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::NOTICE)]
class UserCome
{
    public function __construct(
        public ServerInterface $server,
        public int             $fd,
        public string|int      $id,
        public string          $name,
        public string          $room,
    )
    {

    }
}
