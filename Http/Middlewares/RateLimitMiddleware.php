<?php

declare(strict_types=1);

namespace ManaPHP\Http\Middlewares;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Exception\TooManyRequestsException;
use ManaPHP\Http\Controller\Attribute\RateLimit as RateLimitAttribute;
use ManaPHP\Http\Event\RequestValidating;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Identifying\IdentityInterface;
use ManaPHP\Redis\RedisInterface;
use ReflectionMethod;
use function sprintf;
use function strlen;
use function strpos;
use function substr;
use function trim;

class RateLimitMiddleware
{
    #[Autowired] protected IdentityInterface $identity;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected RedisInterface $redisCache;

    #[Autowired] protected ?string $prefix;

    #[Config] protected string $app_id;

    protected array $rateLimits = [];

    protected function getRateLimit(ReflectionMethod $rMethod): RateLimitAttribute|false
    {
        if (($attributes = $rMethod->getAttributes(RateLimitAttribute::class)) !== []) {
            /** @noinspection PhpIncompatibleReturnTypeInspection */
            return $attributes[0]->newInstance();
        }

        $rClass = $rMethod->getDeclaringClass();
        if (($attributes = $rClass->getAttributes(RateLimitAttribute::class)) !== []) {
            /** @noinspection PhpIncompatibleReturnTypeInspection */
            return $attributes[0]->newInstance();
        }

        return false;
    }

    public function onValidating(#[Event] RequestValidating $event): void
    {
        $controller = $event->controller;
        $action = $event->action;
        $key = "$controller::$action";
        if (($rateLimit = $this->rateLimits[$key] ?? null) === null) {
            $rateLimit = $this->rateLimits[$key] = $this->getRateLimit($event->method);
        }

        if ($rateLimit === false) {
            return;
        }

        $uid = $this->identity->isGuest() ? $this->request->ip() : $this->identity->getName();
        $prefix = ($this->prefix ?? sprintf('cache:%s:rate_limit:', $this->app_id))
            . $event->controller . ':' . $event->action . ':' . $uid . ':';

        foreach ($rateLimit->limits as $k => $v) {
            $v = trim($v);
            if ($pos = strpos($v, '/')) {
                $limit = (int)substr($v, 0, $pos);
                $right = substr($v, $pos + 1);
                $period = seconds(strlen($right) === 1 ? "1$right" : $right);
            } else {
                $limit = (int)$v;
                $period = 60;
            }

            $key = $prefix . $period;

            if ($k === 0 && $rateLimit->burst !== null) {
                if (($used = $this->redisCache->get($key)) === false) {
                    $this->redisCache->setex($key, $period, '1');
                } elseif ($used >= $limit) {
                    throw new TooManyRequestsException();
                } elseif (($left = $this->redisCache->pttl($key)) <= 0) {
                    $this->redisCache->setex($key, $period, '1');
                } else {
                    $ideal = (int)(($period - $left / 1000) * $limit / $period) + 1;
                    if ($used < $ideal) {
                        $diff = $ideal - $used;
                        if ($this->redisCache->incrBy($key, $diff) === $diff) {
                            $this->redisCache->setex($key, $period, '1');
                        }
                    } elseif ($used > $ideal + $rateLimit->burst) {
                        throw new TooManyRequestsException();
                    } elseif ($this->redisCache->incr($key) === 1) {
                        $this->redisCache->expire($key, $period);
                    }
                }
            } elseif (($count = $this->redisCache->incr($key)) === 1) {
                $this->redisCache->expire($key, $period);
            } elseif ($count > $limit) {
                throw new TooManyRequestsException();
            }
        }
    }
}
