<?php

declare(strict_types=1);

namespace ManaPHP\Http\Event;

use ManaPHP\Eventing\Attribute\TraceLevel;
use ManaPHP\Viewing\ViewInterface;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::DEBUG)]
class RequestRendering
{
    public function __construct(
        public ViewInterface $view,
    )
    {

    }
}
