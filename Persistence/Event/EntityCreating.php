<?php

declare(strict_types=1);

namespace ManaPHP\Persistence\Event;

use ManaPHP\Eventing\Attribute\TraceLevel;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::NOTICE)]
class EntityCreating extends AbstractEntityEvent
{
}
