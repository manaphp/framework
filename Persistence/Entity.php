<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

use ArrayAccess;
use JsonSerializable;
use ManaPHP\Persistence\Event\EntityEventInterface;
use Stringable;
use function get_object_vars;
use function is_array;
use function is_object;

class Entity implements ArrayAccess, JsonSerializable, Stringable
{
    public function __construct(array $data = [])
    {
        if ($data) {
            foreach ($data as $field => $value) {
                $this->{$field} = $value;
            }
        }
    }

    /**
     * Assigns values to an entity from an array
     *
     * @param array|Entity $data   =entity_var(new static)
     * @param array        $fields =entity_fields(static::class)
     *
     * @return static
     */
    public function assign(array|Entity $data, array $fields): static
    {
        if (is_object($data)) {
            foreach ($fields as $field) {
                $this->$field = $data->$field;
            }
        } else {
            foreach ($fields as $field) {
                $this->$field = $data[$field];
            }
        }

        return $this;
    }

    /**
     * Returns the instance as an array representation
     *
     * @return array =entity_var(new static)
     */
    public function toArray(): array
    {
        $data = [];

        foreach (get_object_vars($this) as $field => $value) {
            if ($value === null) {
                continue;
            }

            if (is_object($value)) {
                if ($value instanceof self) {
                    $value = $value->toArray();
                } else {
                    continue;
                }
            } elseif (is_array($value) && ($first = current($value)) && $first instanceof self) {
                foreach ($value as $k => $v) {
                    $value[$k] = $v->toArray();
                }
            }

            $data[$field] = $value;
        }

        return $data;
    }

    public function onEvent(EntityEventInterface $entityEvent)
    {
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->$offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->$offset;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->$offset = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->$offset = null;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    public function __toString(): string
    {
        return json_stringify($this->toArray());
    }
}