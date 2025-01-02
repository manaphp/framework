<?php

declare(strict_types=1);

namespace ManaPHP\Http;

class DispatcherContext
{
    public ?string $handler = null;
    public bool $isInvoking = false;
}
