<?php

declare(strict_types=1);

namespace ManaPHP\Http;

interface CookiesInterface
{
    public function all(): array;

    public function set(
        string $name,
        string $value,
        int $expire = 0,
        string $path = '',
        string $domain = '',
        bool $secure = false,
        bool $httponly = true
    ): static;

    public function get(string $name, mixed $default = null): mixed;

    public function has(string $name): bool;

    public function delete(string $name, ?string $path = null, ?string $domain = null): static;
}
