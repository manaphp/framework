<?php

declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Di\Attribute\Autowired;
use ReflectionMethod;

class Interceptor implements InterceptorInterface
{
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;

    public function preHandle(ReflectionMethod $method): bool
    {
        return true;
    }

    public function postHandle(ReflectionMethod $method, mixed &$return): void
    {

    }
}
