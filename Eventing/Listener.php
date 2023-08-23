<?php
declare(strict_types=1);

namespace ManaPHP\Eventing;

use ManaPHP\Logging\Logger\LogCategorizable;

abstract class Listener implements LogCategorizable
{
    public function categorizeLog(): string
    {
        return basename(str_replace('\\', '.', static::class), 'Listener');
    }
}