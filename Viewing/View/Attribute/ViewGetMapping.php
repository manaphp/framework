<?php
declare(strict_types=1);

namespace ManaPHP\Viewing\View\Attribute;

use Attribute;
use ManaPHP\Http\Router\Attribute\GetMapping;

#[Attribute(Attribute::TARGET_METHOD)]
class ViewGetMapping extends GetMapping implements ViewMappingInterface
{

}