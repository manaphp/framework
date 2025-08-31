<?php

declare(strict_types=1);

namespace ManaPHP\Di\Attribute;

use ReflectionMethod;

interface InterceptorInterface
{
    public function preHandle(ReflectionMethod $method): bool;

    public function postHandle(ReflectionMethod $method, mixed &$return): void;
}
