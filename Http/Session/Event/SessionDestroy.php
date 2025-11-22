<?php

declare(strict_types=1);

namespace ManaPHP\Http\Session\Event;

use ManaPHP\Eventing\Attribute\TraceLevel;
use ManaPHP\Http\AbstractSessionContext;
use ManaPHP\Http\SessionInterface;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::NOTICE)]
class SessionDestroy
{
    public function __construct(
        public SessionInterface        $session,
        public ?AbstractSessionContext $context,
        public string                  $session_id,
    )
    {

    }
}
