<?php
declare(strict_types=1);

namespace ManaPHP\Http\Router\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class PatchMapping extends Mapping
{
    public function __construct(?string $path = null)
    {
        parent::__construct($path);

        $this->method = 'PATCH';
    }
}