<?php

declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\EventDispatcherInterface;
use ManaPHP\Eventing\ListenerProviderInterface;
use ManaPHP\Exception\AbortException;
use ManaPHP\Helper\SuppressWarnings;
use ManaPHP\Http\Router\NotFoundRouteException;
use ManaPHP\Http\Server\Event\RequestAuthenticated;
use ManaPHP\Http\Server\Event\RequestAuthenticating;
use ManaPHP\Http\Server\Event\RequestBegin;
use ManaPHP\Http\Server\Event\RequestEnd;
use ManaPHP\Http\Server\Event\RequestException;
use ManaPHP\Http\Server\Event\RequestResponded;
use ManaPHP\Http\Server\Event\RequestResponding;
use ManaPHP\Http\Server\Event\ResponseStringify;
use Throwable;
use function is_array;
use function is_int;
use function is_string;
use function json_stringify;

class Handler implements HandlerInterface
{
    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected RouterInterface $router;
    #[Autowired] protected DispatcherInterface $dispatcher;
    #[Autowired] protected AccessLogInterface $accessLog;
    #[Autowired] protected ServerInterface $httpServer;
    #[Autowired] protected ErrorHandlerInterface $errorHandler;

    #[Autowired] protected array $middlewares = [];

    public function __construct(ListenerProviderInterface $listenerProvider)
    {
        foreach ($this->middlewares as $middleware) {
            if ($middleware !== '' && $middleware !== null) {
                $listenerProvider->add($middleware);
            }
        }
    }

    protected function handleInternal(mixed $actionReturnValue): void
    {
        if ($actionReturnValue === null) {
            $this->response->json(['code' => 0, 'msg' => '']);
        } elseif (is_array($actionReturnValue)) {
            $this->response->json(['code' => 0, 'msg' => '', 'data' => $actionReturnValue]);
        } elseif ($actionReturnValue instanceof Response) {
            SuppressWarnings::noop();
        } elseif (is_string($actionReturnValue)) {
            $this->response->json(['code' => -1, 'msg' => $actionReturnValue]);
        } elseif (is_int($actionReturnValue)) {
            $this->response->json(['code' => $actionReturnValue, 'msg' => '']);
        } elseif ($actionReturnValue instanceof Throwable) {
            $this->errorHandler->handle($actionReturnValue);
        } else {
            $this->response->json(['code' => 0, 'msg' => '', 'data' => $actionReturnValue]);
        }
    }

    /** @noinspection PhpRedundantCatchClauseInspection */
    public function handle(): void
    {
        try {
            $this->eventDispatcher->dispatch(new RequestBegin($this->request));

            $this->eventDispatcher->dispatch(new RequestAuthenticating());
            $this->eventDispatcher->dispatch(new RequestAuthenticated());

            if (($matcher = $this->router->match()) === null) {
                throw new NotFoundRouteException(
                    ['router does not have matched route for `{1} {2}`', $this->request->method(),
                     $this->router->getRewriteUri()]
                );
            }

            $actionReturnValue = $this->dispatcher->dispatch($matcher->getHandler(), $matcher->getParams());

            $this->handleInternal($actionReturnValue);
        } catch (AbortException) {
            SuppressWarnings::noop();
        } catch (Throwable $exception) {
            $this->eventDispatcher->dispatch(new RequestException($exception));
            $this->errorHandler->handle($exception);
        }

        if (is_array($this->response->getContent())) {
            $this->eventDispatcher->dispatch(new ResponseStringify($this->response));
            if (is_array($content = $this->response->getContent())) {
                $this->response->setContent(json_stringify($content));
            }
        }

        $this->eventDispatcher->dispatch(new RequestResponding($this->request, $this->response));
        $this->httpServer->send();
        $this->eventDispatcher->dispatch(new RequestResponded($this->request, $this->response));

        $this->accessLog->log();

        $this->eventDispatcher->dispatch(new RequestEnd($this->request, $this->response));
    }
}
