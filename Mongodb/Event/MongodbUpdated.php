<?php

declare(strict_types=1);

namespace ManaPHP\Mongodb\Event;

use ManaPHP\Eventing\Attribute\TraceLevel;
use ManaPHP\Mongodb\MongodbInterface;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::DEBUG)]
class MongodbUpdated extends AbstractEvent
{
    public function __construct(
        public MongodbInterface $mongodb,
        public string           $namespace,
        public array            $document,
        public array            $filter,
        public int              $count,
    )
    {

    }
}
