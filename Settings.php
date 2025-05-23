<?php

declare(strict_types=1);

namespace ManaPHP;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Redis\RedisDbInterface;

class Settings implements SettingsInterface
{
    #[Autowired] protected RedisDbInterface $redisDb;

    #[Autowired] protected string $key = 'settings';
    #[Autowired] protected int $ttl = 1;

    public function getInternal(string $key, ?string $default = null): ?string
    {
        if (($value = $this->redisDb->hGet($this->key, $key)) === false) {
            if ($default === null) {
                throw new InvalidArgumentException(['`{1}` key is not exists', $key]);
            } else {
                $value = $default;
            }
        }
        return $value;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        if ($this->ttl <= 0) {
            return $this->getInternal($key, $default);
        } else {
            return apcu_remember($this->key . ':' . $key, $this->ttl, fn() => $this->getInternal($key, $default));
        }
    }

    public function set(string $key, string $value): static
    {
        $this->redisDb->hSet($this->key, $key, $value);

        return $this;
    }

    public function exists(string $key): bool
    {
        return $this->redisDb->hExists($this->key, $key);
    }

    public function delete(string $key): static
    {
        $this->redisDb->hDel($this->key, $key);

        return $this;
    }
}
