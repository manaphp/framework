<?php

declare(strict_types=1);

namespace ManaPHP\Viewing\View\Attribute;

use Attribute;
use ManaPHP\Http\Router\Attribute\PutMapping;

#[Attribute(Attribute::TARGET_METHOD)]
class ViewPutMapping extends PutMapping implements ViewMappingInterface
{
}
