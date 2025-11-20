<?php
declare(strict_types=1);

namespace ManaPHP\Logging;

use ManaPHP\Logging\Message\Categorizable;
use Stringable;

class Message implements Stringable, Categorizable
{
    protected function __construct(protected string $category, protected string|Stringable $message)
    {
    }

    public static function of(string $category, string|Stringable $message): static
    {
        return new static($category, $message);
    }

    public function __toString(): string
    {
        return (string)$this->message;
    }

    public function getCategory(): string
    {
        return $this->category;
    }
}