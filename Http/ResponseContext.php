<?php

declare(strict_types=1);

namespace ManaPHP\Http;

class ResponseContext
{
    public int $status_code = 200;
    public string $status_text = 'OK';
    public array $headers = [];
    public array $cookies = [];
    public mixed $content = null;
    public ?string $file = null;
    public bool $chunked = false;
}
