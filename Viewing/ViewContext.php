<?php

declare(strict_types=1);

namespace ManaPHP\Viewing;

class ViewContext
{
    public ?string $layout = null;
    public array $vars = [];
    public string $content;
}
