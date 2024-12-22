<?php
declare(strict_types=1);

namespace ManaPHP\Viewing\View\Attribute;

use Attribute;
use ManaPHP\Http\Router\Attribute\PostMapping;

#[Attribute(Attribute::TARGET_METHOD)]
class ViewPostMapping extends PostMapping implements ViewMappingInterface
{

}