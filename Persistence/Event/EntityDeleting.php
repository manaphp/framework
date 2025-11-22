<?php

declare(strict_types=1);

namespace ManaPHP\Persistence\Event;

use ManaPHP\Eventing\Attribute\TraceLevel;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::DEBUG)]
class EntityDeleting extends AbstractEntityEvent
{
}
