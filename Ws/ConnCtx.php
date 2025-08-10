<?php
declare(strict_types=1);

namespace ManaPHP\Ws;

use ManaPHP\Coroutine\ContextAware;
use ManaPHP\Coroutine\ContextManagerInterface;
use ManaPHP\Di\Attribute\Autowired;

class ConnCtx implements ConnCtxInterface, ContextAware
{
    #[Autowired] protected ContextManagerInterface $contextManager;

    public function getContext(): ConnCtxContext
    {
        return $this->contextManager->getContext($this);
    }

    public function all(): array
    {
        return $this->getContext()->data;
    }

    public function get(string $name, mixed $default = null): mixed
    {
        return $this->getContext()->data[$name] ?? $default;
    }

    public function set(string $name, mixed $value): void
    {
        $this->getContext()->data[$name] = $value;
    }

    public function remove(string $name): void
    {
        unset($this->getContext()->data[$name]);
    }
}