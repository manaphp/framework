<?php

declare(strict_types=1);

namespace ManaPHP\Invoking;

use ReflectionMethod;

interface ArgumentsResolverInterface
{
    public function resolve(ReflectionMethod $rMethod): array;
}
