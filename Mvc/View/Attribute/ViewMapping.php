<?php
declare(strict_types=1);

namespace ManaPHP\Mvc\View\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class ViewMapping implements ViewMappingInterface
{
    public function __construct(public string|array|null $path = null, public ?string $vars = null)
    {

    }

    public function getVars(): ?string
    {
        return $this->vars;
    }

    public function getPath(): string|array|null
    {
        return $this->path;
    }

    public function getMethod(): string
    {
        return 'GET';
    }
}