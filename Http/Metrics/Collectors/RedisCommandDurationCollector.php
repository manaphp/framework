<?php

declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Coroutine\ContextAware;
use ManaPHP\Coroutine\ContextManagerInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\Metrics\FormatterInterface;
use ManaPHP\Http\Metrics\Histogram;
use ManaPHP\Http\Metrics\WorkerCollectorInterface;
use ManaPHP\Redis\Event\RedisCalled;
use function is_array;
use function is_string;
use function preg_match;

class RedisCommandDurationCollector implements WorkerCollectorInterface, ContextAware
{
    #[Autowired] protected ContextManagerInterface $contextManager;
    #[Autowired] protected FormatterInterface $formatter;

    #[Autowired] protected array $buckets = [0.001, 11];
    #[Autowired] protected array $ignored_keys = [];

    protected array $histograms = [];

    public function getContext(): RedisCommandDurationCollectorContext
    {
        return $this->contextManager->getContext($this);
    }

    public function updating(?string $handler): ?array
    {
        $context = $this->getContext();

        return ($handler !== null && $context->commands !== []) ? [$handler, $context->commands] : null;
    }

    public function updated($data): void
    {
        list($handler, $commands) = $data;
        foreach ($commands as list($command, $elapsed)) {
            if (($histogram = $this->histograms[$handler][$command] ?? null) === null) {
                $histogram = $this->histograms[$handler][$command] = new Histogram($this->buckets);
            }
            $histogram->update($elapsed);
        }
    }

    public function onRedisCalled(#[Event] RedisCalled $event): void
    {
        if (($ignored_key = $this->ignored_keys[$event->method] ?? null) === null
            || (is_string($ignored_key) && preg_match($ignored_key, $event->arguments[0]) !== 1)
            || (is_array($ignored_key) && preg_match($ignored_key[0], $event->arguments[$ignored_key[1]]) !== 1)
        ) {
            $context = $this->getContext();

            $context->commands[] = [strtolower($event->method), $event->elapsed];
        }
    }

    public function querying(): array
    {
        return $this->histograms;
    }

    public function export(mixed $data): string
    {
        return $this->formatter->histogram('app_redis_command_duration_seconds', $data, [], ['handler', 'command']);
    }
}
