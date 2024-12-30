<?php

declare(strict_types=1);

namespace ManaPHP\Http;

use ReflectionMethod;

class Interceptor implements InterceptorInterface
{
    public function preHandle(ReflectionMethod $method): bool
    {
        return true;
    }

    public function postHandle(ReflectionMethod $method, mixed &$return): void
    {

    }
}
