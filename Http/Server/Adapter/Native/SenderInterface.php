<?php

declare(strict_types=1);

namespace ManaPHP\Http\Server\Adapter\Native;

interface SenderInterface
{
    public function sendHeaders(): void;

    public function sendBody(): void;
}
