<?php

declare(strict_types=1);

namespace ManaPHP\Db\Event;

use ManaPHP\Db\DbInterface;
use ManaPHP\Eventing\Attribute\TraceLevel;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::DEBUG)]
class DbExecuting
{
    public function __construct(
        public DbInterface $db,
        public string      $event,
        public string      $sql,
        public array       $bind
    )
    {

    }
}
