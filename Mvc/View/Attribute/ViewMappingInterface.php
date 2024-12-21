<?php
declare(strict_types=1);

namespace ManaPHP\Mvc\View\Attribute;

use Attribute;
use ManaPHP\Http\Router\Attribute\MappingInterface;

#[Attribute(Attribute::TARGET_METHOD)]
interface ViewMappingInterface extends MappingInterface
{
    public function getVars(): ?string;
}