<?php

declare(strict_types=1);

namespace ManaPHP\Di;

use Psr\Container\ContainerInterface;
use ReflectionProperty;
use function call_user_func_array;
use function is_string;
use function sprintf;

class Proxy implements Lazy
{
    protected ContainerInterface $container;
    protected ReflectionProperty $property;
    protected object $object;
    protected mixed $value = null;

    public function __construct(
        ContainerInterface $container,
        ReflectionProperty $property,
        object $object,
        mixed $value
    ) {
        $this->container = $container;
        $this->property = $property;
        $this->object = $object;
        $this->value = $value;
    }

    public function __call($name, $args)
    {
        $container = $this->container;

        $proxy = false;
        $type = null;
        foreach ($this->property->getType()?->getTypes() as $rType) {
            if ($rType->getName() === Lazy::class) {
                $proxy = true;
            } else {
                $type = $rType->getName();
            }
        }

        $object = $this->object;
        if (!$proxy) {
            throw new Exception(sprintf('%s::%s is not proxied', $object::class, $this->property->getName()));
        }

        if ($type === null) {
            throw new Exception('no type');
        }

        $value = $this->value;
        if ($value !== null) {
            if (is_string($value)) {
                $value = $container->get($value[0] === '#' ? "$type$value" : $value);
            }
        } else {
            $alias = "$type#" . $this->property->getName();
            $value = $container->has($alias) ? $container->get($alias) : $container->get($type);
        }

        $this->property->setValue($this->object, $value);

        return call_user_func_array([$value, $name], $args);
    }
}
