<?php

declare(strict_types=1);

namespace ManaPHP\Http\Event;

use JsonSerializable;
use ManaPHP\Eventing\Attribute\TraceLevel;
use ManaPHP\Http\Router\MatcherInterface;
use ManaPHP\Http\RouterInterface;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::NOTICE)]
class RequestRouted implements JsonSerializable
{
    public function __construct(
        public RouterInterface $router,
        public ?MatcherInterface $matcher,
    ) {

    }

    public function jsonSerialize(): array
    {
        return [
            'uri'       => $this->router->getRewriteUri(),
            'handler'   => $this->matcher?->getHandler(),
            'variables' => $this->matcher?->getVariables(),
        ];
    }
}
