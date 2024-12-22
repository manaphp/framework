<?php
declare(strict_types=1);

namespace ManaPHP\Mvc\View\Attribute;

use Attribute;
use ManaPHP\Http\Router\Attribute\PatchMapping;

#[Attribute(Attribute::TARGET_METHOD)]
class ViewPatchMapping extends PatchMapping implements ViewMappingInterface
{

}