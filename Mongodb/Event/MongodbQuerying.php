<?php

declare(strict_types=1);

namespace ManaPHP\Mongodb\Event;

use ManaPHP\Eventing\Attribute\TraceLevel;
use ManaPHP\Mongodb\MongodbInterface;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::NOTICE)]
class MongodbQuerying extends AbstractEvent
{
    public function __construct(
        public MongodbInterface $mongodb,
        public string           $namespace,
        public array            $filter,
        public array            $options
    )
    {

    }
}
