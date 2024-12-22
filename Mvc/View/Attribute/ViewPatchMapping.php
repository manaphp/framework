<?php
declare(strict_types=1);

namespace ManaPHP\Mvc\View\Attribute;

use Attribute;
use ManaPHP\Http\Router\Attribute\PatchMapping;

#[Attribute(Attribute::TARGET_METHOD)]
class ViewPatchMapping extends PatchMapping implements ViewMappingInterface
{
    public function __construct(public string|array|null $path = null, public ?string $vars = null)
    {
        parent::__construct($path);
    }

    public function getVars(): ?string
    {
        return $this->vars;
    }
}