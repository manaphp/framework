<?php

declare(strict_types=1);

namespace ManaPHP\Db\Event;

use JsonSerializable;
use Stringable;
use function get_object_vars;
use function json_stringify;

class AbstractEvent implements JsonSerializable, Stringable
{
    public function jsonSerialize(): array
    {
        $vars = get_object_vars($this);
        unset($vars['db'], $vars['pdo']);

        return $vars;
    }

    public function __toString(): string
    {
        return json_stringify($this->jsonSerialize());
    }
}
