<?php
declare(strict_types=1);

namespace ManaPHP\Viewing\View\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class ViewMapping implements ViewMappingInterface
{
    public function __construct(public string|array|null $path = null)
    {

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