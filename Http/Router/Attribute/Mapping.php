<?php
declare(strict_types=1);

namespace ManaPHP\Http\Router\Attribute;

abstract class Mapping implements MappingInterface
{
    public function __construct(protected string|array|null $path = null)
    {
    }

    public function getPath(): string|array|null
    {
        return $this->path;
    }
}