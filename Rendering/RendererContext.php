<?php

declare(strict_types=1);

namespace ManaPHP\Rendering;

use ManaPHP\Coroutine\ContextInseparable;

class RendererContext implements ContextInseparable
{
    public array $sections = [];
    public array $stack = [];
    public array $templates = [];
}
