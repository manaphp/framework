<?php

declare(strict_types=1);

namespace ManaPHP\Coroutine;

interface ContextManagerInterface
{
    public function findContext(ContextAware $object): string;

    public function createContext(ContextAware $object): mixed;

    public function getContext(ContextAware $object, int $cid = 0): mixed;

    public function resetContexts(): void;
}
