<?php

declare(strict_types=1);

namespace ManaPHP\Amqp;

use JsonSerializable;
use function get_object_vars;

class Queue implements JsonSerializable
{
    public string $name;
    public array $features;

    public function __construct(string $name, array $features = [])
    {
        $this->name = $name;

        $this->features = $features + [
                'passive'     => false,
                'durable'     => true,
                'exclusive'   => false,
                'auto_delete' => false,
                'nowait'      => false,
                'arguments'   => [],
            ];
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
