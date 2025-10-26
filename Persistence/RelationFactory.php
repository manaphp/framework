<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

use ManaPHP\Di\TypedFactory;
use ManaPHP\Persistence\Attribute\RelationInterface;

/**
 * @extends TypedFactory<RelationInterface>
 */
class RelationFactory extends TypedFactory
{
    public function make(string $name, array $parameters): RelationInterface
    {
        return $this->container->make($name, $parameters);
    }
}