<?php
declare(strict_types=1);

namespace ManaPHP;

use ManaPHP\Contextor\ContextCreatorInterface;
use ManaPHP\Contextor\ContextInseparable;
use Swoole\Coroutine;

class Contextor implements ContextorInterface
{
    protected array $classes = [];
    protected array $contexts = [];
    protected array $roots = [];

    public function findContext(object $object): ?string
    {
        $class = $object::class;
        if (($context = $this->classes[$class] ?? null) === null) {
            $parent = $class;
            do {
                $try = $parent . 'Context';
                if (class_exists($try)) {
                    $context = $try;
                    break;
                }
            } while ($parent = get_parent_class($parent));

            if ($context === null) {
                return null;
            }

            $this->classes[$class] = $context;
        }

        return $context;
    }

    public function makeContext(object $object)
    {
        if (($context = $this->findContext($object)) === null) {
            throw new Exception(['`%s` context class is not exists', $object::class . 'Context']);
        }

        return new $context();
    }

    public function createContext(object $object): object
    {
        if ($object instanceof ContextCreatorInterface) {
            return $object->createContext();
        } else {
            return $this->makeContext($object);
        }
    }

    public function getContext(object $object): object
    {
        if (MANAPHP_COROUTINE_ENABLED) {
            $object_id = spl_object_id($object);

            if ($ao = Coroutine::getContext()) {
                if (($context = $ao[$object_id] ?? null) === null) {
                    if (($parent_cid = Coroutine::getPcid()) === -1) {
                        return $ao[$object_id] = $this->createContext($object);
                    }

                    $parent_ao = Coroutine::getContext($parent_cid);
                    if (($context = $parent_ao[$object_id] ?? null) !== null) {
                        if ($context instanceof ContextInseparable) {
                            return $ao[$object_id] = $this->createContext($object);
                        } else {
                            return $ao[$object_id] = $context;
                        }
                    } else {
                        $context = $ao[$object_id] = $this->createContext($object);
                        if (!$context instanceof ContextInseparable) {
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
            $object_id = spl_object_id($object);
            if (($context = $this->contexts[$object_id] ?? null) === null) {
                $context = $this->contexts[$object_id] = $this->createContext($object);
            }
            return $context;
        }
    }

    public function hasContext(object $object): bool
    {
        return $this->findContext($object) !== null;
    }

    public function resetContexts(): void
    {
        $this->contexts = [];
    }
}