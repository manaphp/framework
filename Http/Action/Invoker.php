<?php
declare(strict_types=1);

namespace ManaPHP\Http\Action;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\MakerInterface;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Mvc\View\Attribute\ViewMapping;
use ManaPHP\Mvc\View\Attribute\ViewMappingInterface;
use ManaPHP\Mvc\ViewInterface;
use Psr\Container\ContainerInterface;
use ReflectionAttribute;
use ReflectionMethod;
use function is_array;

class Invoker implements InvokerInterface
{
    #[Autowired] protected ContainerInterface $container;
    #[Autowired] protected MakerInterface $maker;
    #[Autowired] protected ArgumentsResolverInterface $argumentsResolver;
    #[Autowired] protected RequestInterface $request;

    protected function invokeMethod(object $object, string $method): mixed
    {
        $arguments = $this->argumentsResolver->resolve($object, $method);

        return $object->$method(...$arguments);
    }

    public function invoke(object $object, string $action): mixed
    {
        if ($this->request->method() === 'GET' && !$this->request->isAjax()) {
            $rMethod = new ReflectionMethod($object, $action);
            $attributes = $rMethod->getAttributes(ViewMappingInterface::class, ReflectionAttribute::IS_INSTANCEOF);
            if ($attributes !== []) {
                $view = $this->container->get(ViewInterface::class);

                /** @var ViewMappingInterface $viewMapping */
                $viewMapping = $attributes[0]->newInstance();
                if ($viewMapping instanceof ViewMapping && is_array($vars = $this->invokeMethod($object, $action))) {
                    $view->setVars($vars);
                }

                return $view;
            }
        }

        return $this->invokeMethod($object, $action);
    }
}