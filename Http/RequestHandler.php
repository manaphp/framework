<?php

declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Coroutine\ContextAware;
use ManaPHP\Coroutine\ContextManagerInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\MakerInterface;
use ManaPHP\Eventing\EventDispatcherInterface;
use ManaPHP\Eventing\ListenerProviderInterface;
use ManaPHP\Exception\AbortException;
use ManaPHP\Helper\SuppressWarnings;
use ManaPHP\Http\Dispatcher\NotFoundActionException;
use ManaPHP\Http\Dispatcher\NotFoundControllerException;
use ManaPHP\Http\Event\RequestAuthenticated;
use ManaPHP\Http\Event\RequestAuthenticating;
use ManaPHP\Http\Event\RequestAuthorized;
use ManaPHP\Http\Event\RequestAuthorizing;
use ManaPHP\Http\Event\RequestBegin;
use ManaPHP\Http\Event\RequestEnd;
use ManaPHP\Http\Event\RequestException;
use ManaPHP\Http\Event\RequestInvoked;
use ManaPHP\Http\Event\RequestInvoking;
use ManaPHP\Http\Event\RequestReady;
use ManaPHP\Http\Event\RequestRendered;
use ManaPHP\Http\Event\RequestRendering;
use ManaPHP\Http\Event\RequestResponded;
use ManaPHP\Http\Event\RequestResponding;
use ManaPHP\Http\Event\RequestRouted;
use ManaPHP\Http\Event\RequestRouting;
use ManaPHP\Http\Event\RequestValidated;
use ManaPHP\Http\Event\RequestValidating;
use ManaPHP\Http\Event\ResponseStringify;
use ManaPHP\Http\Response\AppenderInterface;
use ManaPHP\Http\Router\NotFoundRouteException;
use ManaPHP\Viewing\View\Attribute\ViewMapping;
use ManaPHP\Viewing\View\Attribute\ViewMappingInterface;
use ManaPHP\Viewing\ViewInterface;
use Psr\Container\ContainerInterface;
use ReflectionAttribute;
use ReflectionMethod;
use Throwable;
use function class_exists;
use function explode;
use function is_array;
use function is_int;
use function is_string;
use function json_stringify;
use function method_exists;

class RequestHandler implements RequestHandlerInterface, ContextAware
{
    #[Autowired] protected ContextManagerInterface $contextManager;
    #[Autowired] protected ContainerInterface $container;
    #[Autowired] protected MakerInterface $maker;
    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected RouterInterface $router;
    #[Autowired] protected AccessLogInterface $accessLog;
    #[Autowired] protected ServerInterface $httpServer;
    #[Autowired] protected ErrorHandlerInterface $errorHandler;
    #[Autowired] protected ArgumentsResolverInterface $argumentsResolver;
    #[Autowired] protected ViewInterface $view;

    #[Autowired] protected array $middlewares = [];

    public function __construct(ListenerProviderInterface $listenerProvider)
    {
        foreach ($this->middlewares as $middleware) {
            if ($middleware !== '' && $middleware !== null) {
                $listenerProvider->add($middleware);
            }
        }
    }

    public function getContext(): RequestHandlerContext
    {
        return $this->contextManager->getContext($this);
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

    public function handle(): void
    {
        try {
            $this->eventDispatcher->dispatch(new RequestBegin($this->request));

            $this->eventDispatcher->dispatch(new RequestAuthenticating());
            $this->eventDispatcher->dispatch(new RequestAuthenticated());

            $this->eventDispatcher->dispatch(new RequestRouting($this->router));
            if (($matcher = $this->router->match()) === null) {
                throw new NotFoundRouteException(
                    ['router does not have matched route for `{1} {2}`', $this->request->method(),
                     $this->router->getRewriteUri()]
                );
            }
            $this->eventDispatcher->dispatch(new RequestRouted($this->router, $matcher));

            $context = $this->getContext();

            $this->request->setHandler($matcher->getHandler());
            $globals = $this->request->getContext();
            foreach ($matcher->getParams() as $k => $v) {
                if (is_string($k)) {
                    $globals->_REQUEST[$k] = $v;
                }
            }
            list($controller, $action) = explode('::', $matcher->getHandler());

            if (!class_exists($controller)) {
                throw new NotFoundControllerException(['`{1}` class cannot be loaded', $controller]);
            }

            if (!method_exists($controller, $action)) {
                throw new NotFoundActionException(['`{1}::{2}` method does not exist', $controller, $action]);
            }

            $method = new ReflectionMethod($controller, $action);

            $this->eventDispatcher->dispatch(new RequestAuthorizing($method));
            $this->eventDispatcher->dispatch(new RequestAuthorized($method));

            $this->eventDispatcher->dispatch(new RequestValidating($method));
            $this->eventDispatcher->dispatch(new RequestValidated($method));

            $this->eventDispatcher->dispatch(new RequestReady($method));

            $interceptors = $this->getInterceptors($method);

            foreach ($interceptors as $interceptor) {
                if (!$interceptor->preHandle($method)) {
                    throw new AbortException();
                }
            }

            $this->eventDispatcher->dispatch(new RequestInvoking($method));

            try {
                $context->isInvoking = true;
                $return = $this->invoke($method);
            } finally {
                $context->isInvoking = false;
            }

            $this->eventDispatcher->dispatch(new RequestInvoked($method, $return));

            foreach ($interceptors as $interceptor) {
                $interceptor->postHandle($method, $return);
            }

            $this->handleInternal($return);
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

        foreach ($this->response->getAppenders() as $appender) {
            if ($appender !== '' && $appender !== null) {
                /** @var string|AppenderInterface $appender */
                $appender = $this->container->get($appender);
                $appender->append($this->request, $this->response);
            }
        }

        $this->eventDispatcher->dispatch(new RequestResponding($this->request, $this->response));
        $this->httpServer->send();
        $this->eventDispatcher->dispatch(new RequestResponded($this->request, $this->response));

        $this->accessLog->log();

        $this->eventDispatcher->dispatch(new RequestEnd($this->request, $this->response));
    }

    protected function invoke(ReflectionMethod $rMethod): mixed
    {
        $controller = $this->container->get($rMethod->class);
        $method = $rMethod->name;

        if ($this->request->method() === 'GET' && !$this->request->isAjax()) {
            $attributes = $rMethod->getAttributes(ViewMappingInterface::class, ReflectionAttribute::IS_INSTANCEOF);
            if ($attributes !== []) {
                /** @var ViewMappingInterface $viewMapping */
                $viewMapping = $attributes[0]->newInstance();
                if ($viewMapping instanceof ViewMapping) {
                    $arguments = $this->argumentsResolver->resolve($rMethod);

                    $vars = $controller->$method(...$arguments);
                    if (is_array($vars)) {
                        $this->view->setVars($vars);
                    }
                }

                $this->eventDispatcher->dispatch(new RequestRendering($this->view));
                $content = $this->view->render($this->request->handler());
                $this->eventDispatcher->dispatch(new RequestRendered($this->view));

                return $this->response->setContent($content);
            }
        }

        $arguments = $this->argumentsResolver->resolve($rMethod);

        return $controller->$method(...$arguments);
    }

    /**
     * @param ReflectionMethod $method
     *
     * @return InterceptorInterface[]
     */
    protected function getInterceptors(ReflectionMethod $method): array
    {
        $attributes = [];
        $controller = $method->getDeclaringClass();
        foreach (
            $controller->getAttributes(InterceptorInterface::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute
        ) {
            $attributes[$attribute->getName()] = $attribute;
        }

        foreach ($method->getAttributes(InterceptorInterface::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute)
        {
            $attributes[$attribute->getName()] = $attribute;
        }

        $interceptors = [];
        foreach ($attributes as $attribute) {
            $arguments = $attribute->getArguments();
            $name = $attribute->getName();

            if ($arguments === []) {
                $interceptors[] = $this->container->get($name);
            } else {
                $interceptors[] = $this->maker->make($name, $arguments);
            }
        }

        return $interceptors;
    }

    public function isInvoking(): bool
    {
        return $this->getContext()->isInvoking;
    }
}
