<?php

declare(strict_types=1);

namespace ManaPHP\I18n;

use ManaPHP\Coroutine\ContextAware;
use ManaPHP\Coroutine\ContextManagerInterface;
use ManaPHP\Di\Attribute\Autowired;

class Locale implements LocaleInterface, ContextAware
{
    #[Autowired] protected ContextManagerInterface $contextManager;

    #[Autowired] protected string $default = 'en';

    public function getContext(): LocaleContext
    {
        return $this->contextManager->getContext($this);
    }

    public function get(): string
    {
        return $this->getContext()->locale ?? $this->default;
    }

    public function set(string $locale): static
    {
        $context = $this->getContext();

        $context->locale = $locale;

        return $this;
    }
}
