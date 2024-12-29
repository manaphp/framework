<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Context\ContextTrait;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\Action\ArgumentsResolverInterface;
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
    #[Autowired] protected ContainerInterface $container;
    #[Autowired] protected ArgumentsResolverInterface $argumentsResolver;

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

    protected function invoke(object $object, string $method): mixed
    {
        if ($this->request->method() === 'GET' && !$this->request->isAjax()) {
            $rMethod = new ReflectionMethod($object, $method);
            $attributes = $rMethod->getAttributes(ViewMappingInterface::class, ReflectionAttribute::IS_INSTANCEOF);
            if ($attributes !== []) {
                $view = $this->container->get(ViewInterface::class);

                /** @var ViewMappingInterface $viewMapping */
                $viewMapping = $attributes[0]->newInstance();
                if ($viewMapping instanceof ViewMapping) {
                    $arguments = $this->argumentsResolver->resolve($object, $method);

                    $vars = $object->$method(...$arguments);
                    if (is_array($vars)) {
                        $view->setVars($vars);
                    }
                }

                return $view;
            }
        }

        $arguments = $this->argumentsResolver->resolve($object, $method);

        return $object->$method(...$arguments);
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
        list($context->controller, $context->action) = explode('::', $handler);
        $action = $context->action;

        if (!class_exists($context->controller)) {
            throw new NotFoundControllerException(['`{1}` class cannot be loaded', $context->controller]);
        }

        $controller = $this->container->get($context->controller);

        if (!method_exists($controller, $action)) {
            throw new NotFoundActionException(['`{1}::{2}` method does not exist', $controller::class, $action]);
        }

        $this->eventDispatcher->dispatch(new RequestAuthorizing($this, $controller, $action));
        $this->eventDispatcher->dispatch(new RequestAuthorized($this, $controller, $action));

        $this->eventDispatcher->dispatch(new RequestValidating($this, $controller, $action));
        $this->eventDispatcher->dispatch(new RequestValidated($this, $controller, $action));

        $this->eventDispatcher->dispatch(new RequestReady($this, $controller, $action));

        $this->eventDispatcher->dispatch(new RequestInvoking($this, $controller, $action));

        try {
            $context->isInvoking = true;
            $return = $this->invoke($controller, $action);
        } finally {
            $context->isInvoking = false;
        }

        $this->eventDispatcher->dispatch(new RequestInvoked($this, $controller, $action, $return));

        return $return;
    }

    public function isInvoking(): bool
    {
        /** @var DispatcherContext $context */
        $context = $this->getContext();

        return $context->isInvoking;
    }
}
