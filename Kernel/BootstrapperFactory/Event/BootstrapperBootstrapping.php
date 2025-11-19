<?php
declare(strict_types=1);

namespace ManaPHP\Kernel\BootstrapperFactory\Event;

use JsonSerializable;
use ManaPHP\Eventing\Attribute\TraceLevel;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::INFO)]
class BootstrapperBootstrapping implements JsonSerializable
{
    public function __construct(public string $name)
    {

    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name
        ];
    }
}