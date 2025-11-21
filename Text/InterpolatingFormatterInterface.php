<?php
declare(strict_types=1);

namespace ManaPHP\Text;

use Stringable;
use Throwable;

interface InterpolatingFormatterInterface
{
    public function interpolate(string|Stringable $message, array $context): string;

    public function exceptionToString(Throwable $exception): string;
}