<?php
declare(strict_types=1);

namespace ManaPHP\Ws;

interface ConnCtxInterface
{
    public function set(string $name, mixed $value): void;

    public function get(string $name, mixed $default = null): mixed;

    public function remove(string $name): void;

    public function all();
}