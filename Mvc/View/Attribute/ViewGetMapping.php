<?php
declare(strict_types=1);

namespace ManaPHP\Mvc\View\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class ViewGetMapping extends ViewMapping
{

}