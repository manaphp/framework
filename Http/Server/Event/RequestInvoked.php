<?php

declare(strict_types=1);

namespace ManaPHP\Http\Server\Event;

use ManaPHP\Eventing\Attribute\TraceLevel;
use ManaPHP\Http\DispatcherInterface;
use Psr\Log\LogLevel;
use ReflectionMethod;

#[TraceLevel(LogLevel::DEBUG)]
class RequestInvoked
{
    public string $controller;
    public string $action;

    public function __construct(
        public DispatcherInterface $dispatcher,
        public ReflectionMethod $method,
        public mixed $return,
    ) {
        $this->controller = $this->method->class;
        $this->action = $this->method->name;
    }
}
