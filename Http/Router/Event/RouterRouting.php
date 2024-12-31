<?php

declare(strict_types=1);

namespace ManaPHP\Http\Router\Event;

use ManaPHP\Eventing\Attribute\TraceLevel;
use ManaPHP\Http\RouterInterface;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::DEBUG)]
class RouterRouting
{
    public function __construct(
        public RouterInterface $router
    ) {

    }
}
