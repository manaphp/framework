<?php

declare(strict_types=1);

namespace ManaPHP\Http\Event;

use ManaPHP\Eventing\Attribute\TraceLevel;
use Psr\Log\LogLevel;
use Throwable;

#[TraceLevel(LogLevel::NOTICE)]
class RequestException
{
    public function __construct(
        public Throwable $exception
    )
    {

    }
}
