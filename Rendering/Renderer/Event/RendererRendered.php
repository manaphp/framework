<?php

declare(strict_types=1);

namespace ManaPHP\Rendering\Renderer\Event;

use ManaPHP\Eventing\Attribute\TraceLevel;
use ManaPHP\Rendering\RendererInterface;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::DEBUG)]
class RendererRendered
{
    public function __construct(
        public RendererInterface $renderer,
        public string $template,
        public string $file,
        public array $vars,
    ) {

    }
}
