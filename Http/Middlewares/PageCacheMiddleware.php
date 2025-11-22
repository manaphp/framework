<?php

declare(strict_types=1);

namespace ManaPHP\Http\Middlewares;

use ManaPHP\Coroutine\ContextAware;
use ManaPHP\Coroutine\ContextManagerInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Exception\AbortException;
use ManaPHP\Helper\SuppressWarnings;
use ManaPHP\Http\Controller\Attribute\PageCache as PageCacheAttribute;
use ManaPHP\Http\Event\RequestReady;
use ManaPHP\Http\Event\RequestResponding;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Redis\RedisInterface;
use ReflectionMethod;
use function http_build_query;
use function in_array;
use function is_array;
use function is_int;
use function ksort;
use function max;
use function md5;
use function sprintf;
use function str_contains;

class PageCacheMiddleware implements ContextAware
{
    #[Autowired] protected ContextManagerInterface $contextManager;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected RedisInterface $redisCache;

    #[Config] protected string $app_id;

    protected string $prefix;

    protected array $pageCaches = [];

    /** @noinspection PhpTypedPropertyMightBeUninitializedInspection */
    public function __construct(?string $prefix = null)
    {
        $this->prefix = $prefix ?? sprintf('cache:%s:page_cache:', $this->app_id);
    }

    public function getContext(): PageCacheMiddlewareContext
    {
        return $this->contextManager->getContext($this);
    }

    protected function getPageCache(ReflectionMethod $rMethod): PageCacheAttribute|false
    {
        if (($attributes = $rMethod->getAttributes(PageCacheAttribute::class)) !== []) {
            /** @noinspection PhpIncompatibleReturnTypeInspection */
            return $attributes[0]->newInstance();
        } else {
            return false;
        }
    }

    public function onReady(#[Event] RequestReady $event): void
    {
        if (!in_array($this->request->method(), ['GET', 'POST', 'HEAD'], true)) {
            return;
        }

        $controller = $event->controller;
        $action = $event->action;

        $key = $controller . '::' . $action;
        if (($pageCache = $this->pageCaches[$key] ?? null) === null) {
            $pageCache = $this->pageCaches[$key] = $this->getPageCache($event->method);
        }

        if ($pageCache === false) {
            return;
        }

        $context = $this->getContext();

        $context->ttl = $pageCache->ttl;

        $key = null;
        if ($pageCache->key !== null) {
            $key = $pageCache->key;
            if (is_array($key)) {
                $params = [];
                foreach ((array)$pageCache['key'] as $k => $v) {
                    if (is_int($k)) {
                        $param_name = $v;
                        $param_value = $this->request->input($param_name, '');
                    } else {
                        $param_name = $k;
                        $param_value = $v;
                    }

                    if ($param_value !== '') {
                        $params[$param_name] = $param_value;
                    }
                }

                ksort($params);
                $key = http_build_query($params);
            }
        }

        if ($key === null) {
            $params = [];
            foreach ($this->request->all() as $name => $value) {
                if ($name !== '_url' && $value !== '') {
                    $params[$name] = $value;
                }
            }

            ksort($params);
            $key = http_build_query($params);
        }

        $handler = $controller . '::' . $action;
        if ($key === '') {
            $context->key = $this->prefix . $handler;
        } else {
            $context->key = $this->prefix . $handler . ':' . $key;
        }

        if ($this->request->isAjax()) {
            $context->key .= ':ajax';
        }

        $context->if_none_match = $this->request->header('if-none-match');

        if (($etag = $this->redisCache->hGet($context->key, 'etag')) === false) {
            return;
        }

        if ($etag === $context->if_none_match) {
            $this->response->setNotModified();
            throw new AbortException('The process was terminated by PageCacheMiddleware prematurely.');
        }

        if (!$cache = $this->redisCache->hGetAll($context->key)) {
            return;
        }

        $this->response->setETag($cache['etag']);
        $this->response->setMaxAge(max($this->redisCache->ttl($context->key), 1));

        if (isset($cache['content-type'])) {
            $this->response->setContentType($cache['content-type']);
        }

        if (str_contains($this->request->header('accept-encoding'), 'gzip')) {
            $this->response->setHeader('Content-Encoding', 'gzip');
            $this->response->setContent($cache['content']);
        } else {
            $this->response->setContent(gzdecode($cache['content']));
        }
        $context->cache_used = true;

        throw new AbortException('The process was terminated by PageCacheMiddleware prematurely.');
    }

    public function onResponding(#[Event] RequestResponding $event): void
    {
        SuppressWarnings::unused($event);

        $context = $this->getContext();

        if ($context->cache_used === true || $context->ttl === null || $context->ttl <= 0) {
            return;
        }

        if ($this->response->getStatusCode() !== 200) {
            return;
        }

        $content = $this->response->getContent() ?? '';
        $etag = md5($content);

        $this->redisCache->hMSet(
            $context->key,
            [
                'ttl'          => $context->ttl,
                'etag'         => $etag,
                'content-type' => $this->response->getContentType(),
                'content'      => gzencode($content)
            ]
        );
        $this->redisCache->expire($context->key, $context->ttl);

        if ($context->if_none_match === $etag) {
            $this->response->setNotModified();
        } else {
            $this->response->setMaxAge($context->ttl);
            $this->response->setETag($etag);
        }
    }
}
