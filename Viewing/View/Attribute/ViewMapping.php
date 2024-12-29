<?php

declare(strict_types=1);

namespace ManaPHP\Viewing\View\Attribute;

use Attribute;
use ManaPHP\Http\Router\Attribute\Mapping;

#[Attribute(Attribute::TARGET_METHOD)]
class ViewMapping extends Mapping implements ViewMappingInterface
{
    public function getMethod(): string
    {
        return 'GET';
    }
}
