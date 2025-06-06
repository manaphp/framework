<?php

declare(strict_types=1);

namespace ManaPHP\Viewing;

use ManaPHP\Coroutine\ContextAware;
use ManaPHP\Coroutine\ContextManagerInterface;
use ManaPHP\Di\Attribute\Autowired;

class Flash implements FlashInterface, ContextAware
{
    #[Autowired] protected ContextManagerInterface $contextManager;

    #[Autowired] protected array $css
        = [
            'error'   => 'flash-error',
            'notice'  => 'flash-notice',
            'success' => 'flash-success',
            'warning' => 'flash-warning'
        ];

    public function getContext(): FlashContext
    {
        return $this->contextManager->getContext($this);
    }

    public function error(string $message): void
    {
        $this->message('error', $message);
    }

    public function notice(string $message): void
    {
        $this->message('notice', $message);
    }

    public function success(string $message): void
    {
        $this->message('notice', $message);
    }

    public function warning(string $message): void
    {
        $this->message('warning', $message);
    }

    public function output(bool $remove = true): void
    {
        $context = $this->getContext();

        foreach ($context->messages as $message) {
            echo $message;
        }

        if ($remove) {
            $context->messages = [];
        }
    }

    protected function message(string $type, string $message): void
    {
        $context = $this->getContext();

        $css = $this->css[$type] ?? '';

        $context->messages[] = '<div class="' . $css . '">' . $message . '</div>' . PHP_EOL;
    }
}
