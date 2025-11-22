<?php

declare(strict_types=1);

namespace ManaPHP\Http\Event;

use ReflectionMethod;

class RequestDispatchBase
{
    public string $controller;
    public string $action;

    public function __construct(
        public ReflectionMethod $method,
    )
    {
        $this->controller = $this->method->class;
        $this->action = $this->method->name;
    }
}
