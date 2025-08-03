<?php
declare(strict_types=1);

namespace ManaPHP\Logging;

use Throwable;

interface MessageFormatterInterface
{
    public function format(mixed $message, array $context): string;

    public function interpolate(string $message, array $context): string;

    public function exceptionToString(Throwable $exception): string;
}