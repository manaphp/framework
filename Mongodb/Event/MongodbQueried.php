<?php
declare(strict_types=1);

namespace ManaPHP\Mongodb\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use ManaPHP\Mongodb\MongodbInterface;

#[Verbosity(Verbosity::HIGH)]
class MongodbQueried extends AbstractEvent
{
    public function __construct(
        public MongodbInterface $mongodb,
        public string $namespace,
        public array $filter,
        public array $options,
        public array $result,
        public float $elapsed
    ) {

    }
}