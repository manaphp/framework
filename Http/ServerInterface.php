<?php

declare(strict_types=1);

namespace ManaPHP\Http;

interface ServerInterface
{
    public function start(): void;

    public function sendHeaders(): void;

    public function sendBody(): void;

    public function write(?string $chunk): void;
}
