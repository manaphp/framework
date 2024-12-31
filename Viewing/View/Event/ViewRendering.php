<?php

declare(strict_types=1);

namespace ManaPHP\Viewing\View\Event;

use ManaPHP\Eventing\Attribute\TraceLevel;
use ManaPHP\Viewing\ViewInterface;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::DEBUG)]
class ViewRendering
{
    public function __construct(
        public ViewInterface $view,
    ) {

    }
}
