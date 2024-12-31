<?php

declare(strict_types=1);

namespace ManaPHP\Db\Event;

use ManaPHP\Eventing\Attribute\TraceLevel;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::DEBUG)]
class DbUpdated extends DbExecutedBase
{
}
