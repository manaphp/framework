<?php
declare(strict_types=1);

namespace ManaPHP\Logging;

use Stringable;
use Throwable;

interface MessageFormatterInterface
{
    public function format(string|Stringable $message, array $context): string;

    public function interpolate(string|Stringable $message, array $context): string;

    public function exceptionToString(Throwable $exception): string;
}