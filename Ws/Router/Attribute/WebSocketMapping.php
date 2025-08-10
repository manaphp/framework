<?php

declare(strict_types=1);

namespace ManaPHP\Ws\Router\Attribute;

use Attribute;
use ManaPHP\Http\Router\Attribute\Mapping;

#[Attribute(Attribute::TARGET_CLASS)]
class WebSocketMapping extends Mapping
{
    public function getMethod(): string
    {
        return '*';
    }
}
