<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

use ManaPHP\Di\TypedFactory;
use ManaPHP\Validating\ConstraintInterface;

/**
 * @extends TypedFactory<ConstraintInterface>
 */
class ConstraintFactory extends TypedFactory
{
    public function make(string $name, array $arguments): ConstraintInterface
    {
        return $this->container->make($name, $arguments);
    }
}