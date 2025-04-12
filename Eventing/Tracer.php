<?php

declare(strict_types=1);

namespace ManaPHP\Eventing;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Eventing\Attribute\TraceLevel;
use ManaPHP\Helper\SuppressWarnings;
use ManaPHP\Logging\Event\LoggerLog;
use ManaPHP\Mongodb\Event\MongodbCommanded;
use ManaPHP\Redis\Event\RedisCalled;
use ManaPHP\Redis\Event\RedisCalling;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use ReflectionClass;
use ReflectionMethod;
use ReflectionUnionType;
use Stringable;
use function count;
use function is_string;
use function json_stringify;
use function key;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function stripos;
use function strlen;
use function substr;

class Tracer implements TracerInterface
{
    #[Autowired] protected ListenerProviderInterface $listenerProvider;
    #[Autowired] protected LoggerInterface $logger;

    #[Autowired] protected bool $verbose = true;
    #[Autowired] protected array $levels = [];
    #[Autowired] protected bool $enabled = true;

    #[Config] protected bool $app_debug;

    protected array $listeners = [];

    protected function getListeners(): array
    {
        $listeners = [];

        $rClass = new ReflectionClass($this);
        foreach ($rClass->getMethods(ReflectionMethod::IS_PUBLIC) as $rMethod) {
            if (count($rParameters = $rMethod->getParameters()) !== 1) {
                continue;
            }
            $rParameter = $rParameters[0];
            if ($rParameter->getAttributes(Event::class) !== []) {
                $method = $rMethod->getName();

                $rType = $rParameter->getType();
                if ($rType instanceof ReflectionUnionType) {
                    foreach ($rType->getTypes() as $rType) {
                        $listeners[$rType->getName()] = $method;
                    }
                } else {
                    $listeners[$rType->getName()] = $method;
                }
            }
        }

        return $listeners;
    }

    public function onEvent(object $event): void
    {
        $name = $event::class;

        if (($listener = $this->listeners[$name] ?? null) !== null) {
            $this->$listener($event);
        } else {
            if (($level = $this->levels[$name] ?? null) === null) {
                $rClass = new ReflectionClass($name);
                if (($attributes = $rClass->getAttributes(TraceLevel::class)) !== []) {
                    /** @var TraceLevel $traceLevel */
                    $traceLevel = $attributes[0]->newInstance();
                    $level = $traceLevel->level;
                } else {
                    $level = LogLevel::DEBUG;
                }

                $this->levels[$name] = $level;
            }

            $message = $event instanceof Stringable ? $event : new EventWrapper($event);
            $this->logger->$level($message, ['category' => str_replace('\\', '.', $name)]);
        }
    }

    public function bootstrap(): void
    {
        if ($this->enabled) {
            $this->listeners = $this->getListeners();

            $this->listenerProvider->on('*', [$this, 'onEvent']);
        }
    }

    public function onLoggerLog(#[Event] LoggerLog $event): void
    {
        SuppressWarnings::unused($event);
    }

    public function onRedisCalling(#[Event] RedisCalling $event): void
    {
        $method = $event->method;
        $arguments = $event->arguments;

        $args = substr(json_stringify($arguments, JSON_PARTIAL_OUTPUT_ON_ERROR), 1, -1);
        if (stripos(',blPop,brPop,brpoplpush,subscribe,psubscribe,', ",$method,") !== false) {
            $this->logger->debug("\$redis->$method({0}) ... blocking", [$args, 'category' => 'redis.' . $method]);
        }
    }

    public function onRedisCalled(#[Event] RedisCalled $event): void
    {
        $method = $event->method;
        $arguments = $event->arguments;
        foreach ($arguments as $k => $v) {
            if (is_string($v) && strlen($v) > 128) {
                $arguments[$k] = substr($v, 0, 128) . '...';
            }
        }

        if ($this->verbose) {
            $arguments = json_stringify($arguments, JSON_PARTIAL_OUTPUT_ON_ERROR);
            $return = json_stringify($event->return, JSON_PARTIAL_OUTPUT_ON_ERROR);

            $ret = strlen($return) > 64 ? substr($return, 0, 64) . '...' : $return;
            $args = strlen($arguments) > 256 ? substr($arguments, 1, 256) . '...)' : substr($arguments, 1, -1);
            $this->logger->debug("\$redis->$method({0}) => {1}", [$args, $ret, 'category' => 'redis.' . $method]);
        } else {
            $key = $arguments[0] ?? false;
            if (!$this->app_debug && is_string($key) && str_starts_with($key, 'cache:')) {
                return;
            }
            $arguments = json_stringify($arguments, JSON_PARTIAL_OUTPUT_ON_ERROR);

            $args = strlen($arguments) > 256 ? substr($arguments, 1, 256) . '...)' : substr($arguments, 1, -1);
            $this->logger->debug("\$redis->$method({0})", [$args, 'category' => 'redis.' . $method]);
        }
    }

    public function onMongodbCommanded(#[Event] MongodbCommanded $event): void
    {
        $command_name = key($event->command);

        if (str_contains(
            'ping,aggregate,count,distinct,group,mapReduce,geoNear,geoSearch,find,' .
            'authenticate,listDatabases,listCollections,listIndexes',
            $command_name
        )
        ) {
            $this->logger->debug($event, ['category' => 'mongodb.command.' . $command_name]);
        } else {
            $this->logger->info($event, ['category' => 'mongodb.command.' . $command_name]);
        }
    }
}
