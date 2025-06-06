<?php

declare(strict_types=1);

namespace ManaPHP\Di;

interface InvokerInterface
{
    public function call(callable $callable, array $parameters = []): mixed;
}
