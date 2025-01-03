<?php

declare(strict_types=1);

namespace ManaPHP\Http\Event;

use ManaPHP\Eventing\Attribute\TraceLevel;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::DEBUG)]
class RequestInvoking extends RequestDispatchBase
{
}
