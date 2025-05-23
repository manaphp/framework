<?php

declare(strict_types=1);

namespace ManaPHP\Pooling\Pool\Event;

use ManaPHP\Eventing\Attribute\TraceLevel;
use ManaPHP\Pooling\PoolsInterface;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::DEBUG)]
class PoolPopping
{
    public function __construct(
        public PoolsInterface $pools,
        public object $owner,
        public string $type,
    ) {

    }

    public function __toString()
    {
        return json_stringify(
            ['owner' => $this->owner::class,
             'type'  => $this->type
            ]
        );
    }
}
