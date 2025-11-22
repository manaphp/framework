<?php

declare(strict_types=1);

namespace ManaPHP\Db\Event;

use ManaPHP\Db\DbInterface;
use ManaPHP\Eventing\Attribute\TraceLevel;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::NOTICE)]
class DbBegin
{
    public function __construct(
        public DbInterface $db,
    )
    {

    }
}
