<?php
declare(strict_types=1);

namespace ManaPHP\Ws\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class ErrorMapping implements MappingInterface
{

}