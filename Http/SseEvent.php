<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use Stringable;
use function sprintf;

class SseEvent implements Stringable
{
    protected array $data = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function __toString(): string
    {
        $event = '';
        foreach ($this->data as $key => $value) {
            $event .= sprintf('%s: %s', $key, $value) . "\r\n";
        }
        $event .= "\r\n";

        return $event;
    }
}