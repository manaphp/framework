<?php

declare(strict_types=1);

namespace ManaPHP\Http\Session\Event;

use JsonSerializable;
use ManaPHP\Eventing\Attribute\TraceLevel;
use ManaPHP\Http\AbstractSessionContext;
use ManaPHP\Http\SessionInterface;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::INFO)]
class SessionUpdate implements JsonSerializable
{
    public function __construct(
        public SessionInterface       $session,
        public AbstractSessionContext $context,
        public string                 $session_id,
    )
    {

    }

    public function jsonSerialize(): array
    {
        return [
            'session_id' => $this->session_id,
            'SESSION'    => $this->context->_SESSION,
        ];
    }
}
