<?php

declare(strict_types=1);

namespace ManaPHP\Eventing\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class TraceLevel
{
    public function __construct(public string $level)
    {

    }
}
