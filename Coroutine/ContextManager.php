<?php

declare(strict_types=1);

namespace ManaPHP\Coroutine;

use ReflectionMethod;
use Swoole\Coroutine;
use function spl_object_id;

class ContextManager implements ContextManagerInterface
{
    protected array $classes = [];
    protected array $contexts = [];
    protected array $roots = [];

    public function findContext(ContextAware $object): string
    {
        $class = $object::class;
        if (($context = $this->classes[$class] ?? null) === null) {
            $method = new ReflectionMethod($object, 'getContext');
            $returnType = $method->getReturnType();

            if ($returnType !== null && !$returnType->isBuiltin()) {
                $context = $this->classes[$class] = $returnType->getName();
            } else {
                throw new ContextException('The context class cannot be inferred for "{class}".', ['class' => $class]);
            }
        }

        return $context;
    }

    public function createContext(ContextAware $object): mixed
    {
        $context = $this->findContext($object);

        return new $context();
    }

    public function getContext(ContextAware $object, int $cid = 0): mixed
    {
        $object_id = spl_object_id($object);

        if (MANAPHP_COROUTINE_ENABLED) {
            if ($ao = Coroutine::getContext($cid)) {
                if (($context = $ao[$object_id] ?? null) === null) {
                    if (($parent_cid = Coroutine::getPcid()) === -1) {
                        return $ao[$object_id] = $this->createContext($object);
                    }

                    $parent_ao = Coroutine::getContext($parent_cid);
                    /** @noinspection NullPointerExceptionInspection */
                    if (($context = $parent_ao[$object_id] ?? null) !== null) {
                        if ($context instanceof ContextInseparable) {
                            return $ao[$object_id] = $this->createContext($object);
                        } else {
                            return $ao[$object_id] = $context;
                        }
                    } else {
                        $context = $ao[$object_id] = $this->createContext($object);
                        if (!$context instanceof ContextInseparable) {
                            /** @noinspection NullPointerExceptionInspection */
                            $parent_ao[$object_id] = $context;
                        }
                    }
                }
                return $context;
            } elseif (($context = $this->roots[$object_id] ?? null) === null) {
                return $this->roots[$object_id] = $this->createContext($object);
            } else {
                return $context;
            }
        } else {
            if (($context = $this->contexts[$object_id] ?? null) === null) {
                $context = $this->contexts[$object_id] = $this->createContext($object);
            }
            return $context;
        }
    }


    public function resetContexts(): void
    {
        $this->contexts = [];
    }
}
