<?php

declare(strict_types=1);

namespace ManaPHP\Cli\Event;

use ManaPHP\Cli\HandlerInterface;
use ManaPHP\Eventing\Attribute\TraceLevel;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::INFO)]
class CliInvoking
{
    public function __construct(
        public HandlerInterface $handler,
        public object           $command,
        public string           $method,
        public string           $action,
    )
    {

    }
}
