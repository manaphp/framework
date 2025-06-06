<?php

declare(strict_types=1);

namespace ManaPHP\Pooling;

use ManaPHP\Coroutine\Channel;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\MakerInterface;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Pooling\Pool\Event\PoolBusy;
use ManaPHP\Pooling\Pool\Event\PoolPopped;
use ManaPHP\Pooling\Pool\Event\PoolPopping;
use ManaPHP\Pooling\Pool\Event\PoolPush;
use Psr\EventDispatcher\EventDispatcherInterface;
use WeakMap;
use function is_array;
use function microtime;

class Pools implements PoolsInterface
{
    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected MakerInterface $maker;

    protected WeakMap $pools;

    public function __construct()
    {
        $this->pools = new WeakMap();
    }

    public function remove(object $owner, ?string $type = null): void
    {
        if ($type === null) {
            unset($this->pools[$owner]);
        } else {
            unset($this->pools[$owner][$type]);
        }
    }

    public function create(object $owner, int $capacity, string $type = 'default'): void
    {
        if (isset($this->pools[$owner][$type])) {
            throw new MisuseException(['`{1}` pool of `{2}` is exists', $type, $owner::class]);
        }

        $this->pools[$owner] ??= [];
        $this->pools[$owner][$type] = new Channel($capacity);
    }

    public function add(object $owner, object|array $sample, int $size = 1, string $type = 'default'): void
    {
        if (!$queue = $this->pools[$owner][$type] ?? null) {
            $this->pools[$owner] ??= [];
            $this->pools[$owner][$type] = $queue = new Channel($size);
        } else {
            if ($queue->length() + $size > $queue->capacity()) {
                throw new FullException(
                    ['`{1}` pool of `{2}` capacity({3}) is not big enough', $type, $owner::class, $queue->capacity()]
                );
            }
        }

        if (is_array($sample)) {
            $sample = $this->maker->make($sample[0], $sample[1] ?? []);
        }

        $queue->push($sample);

        for ($i = 1; $i < $size; $i++) {
            $queue->push(clone $sample);
        }
    }

    public function push(object $owner, object $instance, string $type = 'default'): void
    {
        if (!$queue = $this->pools[$owner][$type] ?? null) {
            throw new MisuseException(['`{1}` pool of `{2}` is not exists', $type, $owner::class]);
        }

        $queue->push($instance);

        $this->eventDispatcher->dispatch(new PoolPush($this, $owner, $instance, $type));
    }

    public function pop(object $owner, ?float $timeout = null, string $type = 'default'): object
    {
        if (!$queue = $this->pools[$owner][$type] ?? null) {
            throw new MisuseException(['`{1}` pool of `{2}` is not exists', $type, $owner::class]);
        }

        $this->eventDispatcher->dispatch(new PoolPopping($this, $owner, $type));
        $start_time = microtime(true);
        if (!$instance = $timeout ? $queue->pop($timeout) : $queue->pop()) {
            $this->eventDispatcher->dispatch(
                new PoolPopped($this, $owner, $instance, $type, microtime(true) - $start_time)
            );
            $capacity = $queue->capacity();
            $this->eventDispatcher->dispatch(new PoolBusy($this, $owner, $type, $capacity, $timeout));
            throw new BusyException(['`{1}` pool of `{2}` is busy: capacity[{3}]', $type, $owner::class, $capacity]);
        }

        $this->eventDispatcher->dispatch(
            new PoolPopped($this, $owner, $instance, $type, microtime(true) - $start_time)
        );

        return $instance;
    }

    public function get(object $owner, ?float $timeout = null, string $type = 'default'): Proxy
    {
        $instance = $this->pop($owner, $timeout, $type);

        return new Proxy($this, $owner, $instance, $type);
    }

    public function isEmpty(object $owner, string $type = 'default'): bool
    {
        if (!$queue = $this->pools[$owner][$type] ?? null) {
            throw new MisuseException(['`{1}` pool of `{2}` is not exists', $type, $owner::class]);
        }

        return $queue->isEmpty();
    }

    public function exists(object $owner, string $type = 'default'): bool
    {
        return isset($this->pools[$owner][$type]);
    }

    public function size(object $owner, string $type = 'default'): int
    {
        if (!$queue = $this->pools[$owner][$type] ?? null) {
            throw new MisuseException(['`{1} pool of `{2}` is not exists', $type, $owner::class]);
        }

        return $queue->capacity();
    }
}
