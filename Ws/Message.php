<?php
declare(strict_types=1);

namespace ManaPHP\Ws;

class Message
{
    protected int $fd;

    protected string $payload;

    public function __construct(int $fd, string $payload)
    {
        $this->fd = $fd;
        $this->payload = $payload;
    }

    public function getFd(): int
    {
        return $this->fd;
    }

    public function getPayload(): string
    {
        return $this->payload;
    }
}