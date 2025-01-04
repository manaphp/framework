<?php

declare(strict_types=1);

namespace ManaPHP\Http\Router;

use ManaPHP\Helper\Str;

use function preg_replace_callback;
use function str_contains;

class Matcher implements MatcherInterface
{
    protected string $handler;
    protected array $variables;

    public function __construct(string $handler, array $variables = [])
    {
        if (str_contains($handler, '{')) {
            $handler = preg_replace_callback('#{([^}]+)}#', static function ($match) use (&$variables) {
                $name = $match[1];
                $value = $variables[$name];

                unset($variables[$name]);
                return $name === 'action' ? Str::camelize($value) : Str::pascalize($value);
            }, $handler);
        }

        $this->handler = $handler;
        $this->variables = $variables;
    }

    public function getHandler(): string
    {
        return $this->handler;
    }

    public function getVariables(): array
    {
        return $this->variables;
    }
}
