<?php

declare(strict_types=1);

namespace ManaPHP\I18n;

use ManaPHP\Coroutine\ContextAware;
use ManaPHP\Coroutine\ContextCreatorInterface;
use ManaPHP\Coroutine\ContextManagerInterface;
use ManaPHP\Di\Attribute\Autowired;

class Locale implements LocaleInterface, ContextCreatorInterface, ContextAware
{
    #[Autowired] protected ContextManagerInterface $contextManager;

    #[Autowired] protected string $default = 'en';

    public function getContext(): LocaleContext
    {
        return $this->contextManager->getContext($this);
    }

    public function createContext(): LocaleContext
    {
        $context = $this->contextManager->makeContext($this);

        $context->locale = $this->default;

        return $context;
    }

    public function get(): string
    {
        return $this->getContext()->locale;
    }

    public function set(string $locale): static
    {
        $context = $this->getContext();

        $context->locale = $locale;

        return $this;
    }
}
