<?php

declare(strict_types=1);

namespace ManaPHP\Ws;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\AbortException;
use ManaPHP\Helper\SuppressWarnings;
use ManaPHP\Http\ErrorHandler;
use ManaPHP\Http\Event\RequestAuthenticated;
use ManaPHP\Http\Event\RequestAuthenticating;
use ManaPHP\Http\Event\RequestBegin;
use ManaPHP\Http\Event\RequestEnd;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\Response;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Http\Router\NotFoundRouteException;
use ManaPHP\Http\RouterInterface;
use ManaPHP\Identifying\IdentityInterface;
use ManaPHP\Ws\Server\Event\Close;
use ManaPHP\Ws\Server\Event\Open;
use Psr\EventDispatcher\EventDispatcherInterface;
use Throwable;
use function is_array;
use function is_int;
use function is_string;
use function json_stringify;

class Handler implements HandlerInterface
{
    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected ServerInterface $wsServer;
    #[Autowired] protected IdentityInterface $identity;
    #[Autowired] protected RouterInterface $router;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected ErrorHandler $errorHandler;

    /**
     * @noinspection PhpRedundantCatchClauseInspection
     * @noinspection PhpUnusedLocalVariableInspection
     */
    public function handle(int $fd, string $event): void
    {
        try {
            $throwable = null;

            $this->eventDispatcher->dispatch(new RequestBegin($this->request));

            if ($event === 'open') {
                $this->eventDispatcher->dispatch(new RequestAuthenticating());
                $this->eventDispatcher->dispatch(new RequestAuthenticated());
            }

            if (($matcher = $this->router->match()) === null) {
                throw new NotFoundRouteException(['router does not have matched route']);
            }

            $returnValue = $this->dispatch($matcher->getHandler(), $matcher->getVariables());

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

            if ($event === 'open') {
                $this->eventDispatcher->dispatch(new Open($fd));
            } elseif ($event === 'close') {
                $this->eventDispatcher->dispatch(new Close($fd));
            }
        } catch (AbortException $exception) {
            SuppressWarnings::noop();
        } catch (Throwable $throwable) {
            $this->errorHandler->handle($throwable);
        }

        if ($content = $this->response->getContent()) {
            if (!is_string($content)) {
                $content = json_stringify($content);
                $this->response->setContent($content);
            }
            $this->wsServer->push($fd, $content);
        }

        $this->eventDispatcher->dispatch(new RequestEnd($this->request, $this->response));

        if ($throwable) {
            $this->wsServer->disconnect($fd);
        }
    }

    public function onOpen(int $fd): void
    {
        $this->handle($fd, 'open');
    }

    public function onClose(int $fd): void
    {
        $this->handle($fd, 'close');
    }

    public function onMessage(int $fd, string $data): void
    {
        $this->request->set('data', $data);
        $this->handle($fd, 'message');
        $this->request->delete('data');
    }

    /** @noinspection PhpMixedReturnTypeCanBeReducedInspection */
    public function dispatch(string $handler, array $params): mixed
    {
        SuppressWarnings::unused($handler);
        SuppressWarnings::unused($params);

        return 0;
    }
}
