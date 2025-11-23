<?php

declare(strict_types=1);

namespace ManaPHP\Ws;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\InvokerInterface;
use ManaPHP\Exception\AbortException;
use ManaPHP\Helper\SuppressWarnings;
use ManaPHP\Http\ErrorHandlerInterface;
use ManaPHP\Http\Event\RequestAuthenticated;
use ManaPHP\Http\Event\RequestAuthenticating;
use ManaPHP\Http\Event\ResponseStringify;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\Response;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Http\Router\NotFoundRouteException;
use ManaPHP\Http\RouterInterface;
use ManaPHP\Identifying\IdentityInterface;
use ManaPHP\Ws\Attribute\CloseMapping;
use ManaPHP\Ws\Attribute\MappingInterface;
use ManaPHP\Ws\Attribute\MessageMapping;
use ManaPHP\Ws\Attribute\OpenMapping;
use ManaPHP\Ws\Server\Event\Close;
use ManaPHP\Ws\Server\Event\Open;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use Throwable;
use function explode;
use function is_array;
use function is_int;
use function is_string;
use function json_parse;
use function json_stringify;
use function str_ends_with;
use function str_starts_with;

class Handler implements HandlerInterface
{
    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected ServerInterface $wsServer;
    #[Autowired] protected IdentityInterface $identity;
    #[Autowired] protected RouterInterface $router;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected ErrorHandlerInterface $errorHandler;
    #[Autowired] protected ConnCtxInterface $connCtx;
    #[Autowired] protected InvokerInterface $invoker;
    #[Autowired] protected ControllerFactory $controllerFactory;

    public const CONN_CTX_HANDLER = '.handler';
    public const CONN_CTX_FD = '.fd';

    protected array $mappings = [];

    protected function getMappings(string $handler): array
    {
        if (($mappings = $this->mappings[$handler] ?? null) !== null) {
            return $mappings;
        }

        $mappings = [];
        $rc = new ReflectionClass($handler);
        foreach ($rc->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $attributes = $method->getAttributes(MappingInterface::class, ReflectionAttribute::IS_INSTANCEOF);
            if ($attributes !== []) {
                $mapping = $attributes[0]->newInstance();
                if ($mapping instanceof MessageMapping) {
                    $mappings[MessageMapping::class][$mapping->pattern] = $method->getName();
                } else {
                    $mappings[$mapping::class] = $method->getName();
                }
            }
        }

        return $this->mappings[$handler] = $mappings;
    }

    public function onOpen(int $fd): void
    {
        try {
            $this->eventDispatcher->dispatch(new RequestAuthenticating());
            $this->eventDispatcher->dispatch(new RequestAuthenticated());

            if (($matcher = $this->router->match()) === null) {
                throw new NotFoundRouteException('No handler found for the incoming WebSocket request.', ['request_path' => $this->request->path(), 'request_method' => $this->request->method()]);
            }

            $handler = $matcher->getHandler();

            $this->connCtx->set(self::CONN_CTX_HANDLER, $handler);
            $this->connCtx->set(self::CONN_CTX_FD, $fd);

            $method = $this->getMappings($handler)[OpenMapping::class] ?? null;
            if ($method !== null) {
                $this->dispatch($handler, $method, ['fd' => $fd]);
            }
            $this->eventDispatcher->dispatch(new Open($fd));
        } catch (AbortException $exception) {
            SuppressWarnings::unused($exception);
        } catch (Throwable $throwable) {
            $this->errorHandler->handle($throwable);
        }

        $this->sendResponse($fd);
    }

    public function onClose(int $fd): void
    {
        try {
            $handler = $this->connCtx->get(self::CONN_CTX_HANDLER);
            $method = $this->getMappings($handler)[CloseMapping::class] ?? null;
            if ($method !== null) {
                $this->dispatch($handler, $method, ['fd' => $fd]);
            }

            $this->eventDispatcher->dispatch(new Close($fd));
        } catch (AbortException $exception) {
            SuppressWarnings::unused($exception);
        } catch (Throwable $throwable) {
            $this->errorHandler->handle($throwable);
        }

        $this->sendResponse($fd);
    }

    public function onMessage(int $fd, string $data): void
    {
        $this->request->set('data', $data);

        try {
            $handler = $this->connCtx->get(self::CONN_CTX_HANDLER);
            if (str_starts_with($data, '{') && str_ends_with($data, '}')) {
                $parameters = json_parse($data) ?? [];
            } else {
                $parameters = [];
            }
            $parameters[Message::class] = new Message($fd, $data);

            $mappings = $this->getMappings($handler)[MessageMapping::class] ?? null;
            $method = $mappings[''] ?? null;
            foreach ($mappings ?? [] as $pattern => $m) {
                if ($pattern !== '') {
                    list($key, $value) = explode('=', $pattern, 2);
                    if ((string)($parameters[$key] ?? null) === $value) {
                        $method = $m;
                    }
                }
            }

            if ($method !== null) {
                $this->dispatch($handler, $method, $parameters);
            }
        } catch (AbortException $exception) {
            SuppressWarnings::unused($exception);
        } catch (Throwable $throwable) {
            $this->errorHandler->handle($throwable);
        }

        $this->sendResponse($fd);

        $this->request->delete('data');
    }

    public function dispatch(string $handler, string $method, array $parameters): void
    {
        $instance = $this->controllerFactory->get($handler);

        $returnValue = $this->invoker->call([$instance, $method], $parameters);

        if ($returnValue === null || $returnValue instanceof Response) {
            SuppressWarnings::noop();
        } elseif (is_string($returnValue)) {
            $this->response->json(['code' => $returnValue, 'msg' => '']);
        } elseif (is_array($returnValue)) {
            $this->response->json(['code' => 0, 'msg' => '', 'data' => $returnValue]);
        } elseif (is_int($returnValue)) {
            $this->response->json(['code' => $returnValue, 'msg' => '']);
        } else {
            $this->response->json($returnValue);
        }
    }

    protected function sendResponse(int $fd): void
    {
        if (($content = $this->response->getContent()) === null) {
            return;
        }

        if (!is_string($content)) {
            $this->eventDispatcher->dispatch(new ResponseStringify($this->response));
            if (!is_string($content = $this->response->getContent())) {
                $this->response->setContent($content = json_stringify($content));
            }
        }

        $this->wsServer->push($fd, $content);
    }
}
