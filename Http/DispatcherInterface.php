<?php

declare(strict_types=1);

namespace ManaPHP\Http;

interface DispatcherInterface
{
    public function getHandler(): ?string;

    public function dispatch(string $handler, array $params): mixed;

    public function isInvoking(): bool;
}
