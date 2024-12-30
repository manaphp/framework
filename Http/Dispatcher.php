<?php

declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Context\ContextTrait;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\MakerInterface;
use ManaPHP\Http\Dispatcher\NotFoundActionException;
use ManaPHP\Http\Dispatcher\NotFoundControllerException;
use ManaPHP\Http\Server\Event\RequestAuthorized;
use ManaPHP\Http\Server\Event\RequestAuthorizing;
use ManaPHP\Http\Server\Event\RequestInvoked;
use ManaPHP\Http\Server\Event\RequestInvoking;
use ManaPHP\Http\Server\Event\RequestReady;
use ManaPHP\Http\Server\Event\RequestValidated;
use ManaPHP\Http\Server\Event\RequestValidating;
use ManaPHP\Viewing\View\Attribute\ViewMapping;
use ManaPHP\Viewing\View\Attribute\ViewMappingInterface;
use ManaPHP\Viewing\ViewInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionAttribute;
use ReflectionMethod;
use function explode;
use function is_array;
use function is_string;
use function method_exists;

class Dispatcher implements DispatcherInterface
{
    use ContextTrait;

    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected ContainerInterface $container;
    #[Autowired] protected MakerInterface $maker;
    #[Autowired] protected ArgumentsResolverInterface $argumentsResolver;
    #[Autowired] protected ViewInterface $view;

    public function getController(): ?string
    {
        /** @var DispatcherContext $context */
        $context = $this->getContext();

        return $context->controller;
    }

    public function getAction(): ?string
    {
        /** @var DispatcherContext $context */
        $context = $this->getContext();

        return $context->action;
    }

    public function getParams(): array
    {
        /** @var DispatcherContext $context */
        $context = $this->getContext();

        return $context->params;
    }

    public function getHandler(): ?string
    {
        /** @var DispatcherContext $context */
        $context = $this->getContext();

        return $context->handler;
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

                return $this->response->setContent($this->view->render($this->getHandler()));
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

    public function dispatch(string $handler, array $params): mixed
    {
        /** @var DispatcherContext $context */
        $context = $this->getContext();

        $context->handler = $handler;
        $context->params = $params;

        $globals = $this->request->getContext();

        foreach ($params as $k => $v) {
            if (is_string($k)) {
                $globals->_REQUEST[$k] = $v;
            }
        }
        list($controller, $action) = explode('::', $handler);
        $context->controller = $controller;
        $context->action = $action;

        if (!class_exists($controller)) {
            throw new NotFoundControllerException(['`{1}` class cannot be loaded', $controller]);
        }

        if (!method_exists($controller, $action)) {
            throw new NotFoundActionException(['`{1}::{2}` method does not exist', $controller, $action]);
        }

        $method = new ReflectionMethod($controller, $action);

        $this->eventDispatcher->dispatch(new RequestAuthorizing($this, $method));
        $this->eventDispatcher->dispatch(new RequestAuthorized($this, $method));

        $this->eventDispatcher->dispatch(new RequestValidating($this, $method));
        $this->eventDispatcher->dispatch(new RequestValidated($this, $method));

        $this->eventDispatcher->dispatch(new RequestReady($this, $method));

        $interceptors = $this->getInterceptors($method);

        foreach ($interceptors as $interceptor) {
            if (!$interceptor->preHandle($method)) {
                return $this->response;
            }
        }

        $this->eventDispatcher->dispatch(new RequestInvoking($this, $method));

        try {
            $context->isInvoking = true;
            $return = $this->invoke($method);
        } finally {
            $context->isInvoking = false;
        }

        $this->eventDispatcher->dispatch(new RequestInvoked($this, $method, $return));

        foreach ($interceptors as $interceptor) {
            $interceptor->postHandle($method, $return);
        }

        return $return;
    }

    public function isInvoking(): bool
    {
        /** @var DispatcherContext $context */
        $context = $this->getContext();

        return $context->isInvoking;
    }
}
