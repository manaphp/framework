<?php

declare(strict_types=1);

namespace ManaPHP\Http;

interface RequestHandlerInterface
{
    public function handle(): void;
}
