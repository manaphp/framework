<?php
declare(strict_types=1);

namespace ManaPHP\Di\Event;

use JsonSerializable;
use ManaPHP\Eventing\Attribute\TraceLevel;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::INFO)]
class FactoryObjectInjected implements JsonSerializable
{
    public function __construct(public string $type, public string $name, public string $id)
    {

    }

    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type,
            'name' => $this->name,
            'id' => $this->id
        ];
    }
}