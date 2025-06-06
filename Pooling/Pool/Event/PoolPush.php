<?php

declare(strict_types=1);

namespace ManaPHP\Pooling\Pool\Event;

use ManaPHP\Eventing\Attribute\TraceLevel;
use ManaPHP\Pooling\PoolsInterface;
use Psr\Log\LogLevel;
use Stringable;

#[TraceLevel(LogLevel::DEBUG)]
class PoolPush implements Stringable
{
    public function __construct(
        public PoolsInterface $pools,
        public object $owner,
        public object $instance,
        public string $type,
    ) {

    }

    public function __toString(): string
    {
        return json_stringify(
            ['owner'    => $this->owner::class,
             'instance' => $this->instance::class . '#' . spl_object_id($this->instance),
             'type'     => $this->type
            ]
        );
    }
}
