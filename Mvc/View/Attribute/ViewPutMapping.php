<?php
declare(strict_types=1);

namespace ManaPHP\Mvc\View\Attribute;

use Attribute;
use ManaPHP\Http\Router\Attribute\PutMapping;

#[Attribute(Attribute::TARGET_METHOD)]
class ViewPutMapping extends PutMapping implements ViewMappingInterface
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