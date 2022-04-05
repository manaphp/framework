<?php
declare(strict_types=1);

namespace ManaPHP\Di;

use Closure;
use ManaPHP\Exception\MissingFieldException;
use ManaPHP\Exception\MisuseException;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use WeakMap;

class Container implements ContainerInterface, \Psr\Container\ContainerInterface
{
    protected array $definitions = [];
    protected array $instances = [];
    protected WeakMap $dependencies;
    protected array $types = [];

    public function __construct(array $definitions = [])
    {
        $this->definitions = $definitions;
        $this->definitions['ManaPHP\Di\ContainerInterface'] = $this;
        $this->definitions['Psr\Container\ContainerInterface'] = $this;

        $this->dependencies = new WeakMap();
    }

    public function set(string $id, mixed $definition): static
    {
        if (isset($this->instances[$id])) {
            throw new MisuseException(['it\'s too late to set(): `%s` instance has been created', $id]);
        }

        $this->definitions[$id] = $definition;

        return $this;
    }

    public function remove(string $id): static
    {
        unset($this->definitions[$id], $this->instances[$id]);

        return $this;
    }

    public function make(string $class, array $parameters = []): mixed
    {
        if (is_string(($alias = $this->definition[$class] ?? null))) {
            return $this->make($alias, $parameters);
        }

        $exists = false;
        /** @noinspection NotOptimalIfConditionsInspection */
        if (str_ends_with($class, 'Interface') && interface_exists($class)) {
            $prefix = substr($class, 0, -9);
            if (class_exists($prefix)) {
                $exists = true;
                $class = $prefix;
            } elseif (class_exists($factory = $prefix . 'Factory')) {
                $exists = true;
                $class = $factory;
            }
        } elseif (class_exists($class)) {
            $exists = true;
        }

        if (!$exists) {
            throw new NotFoundException(['`%s` is not exists', $class]);
        }

        if (is_subclass_of($class, FactoryInterface::class)) {
            /** @var \ManaPHP\Di\FactoryInterface $factory */
            $factory = new $class();
            return $factory->make($this, '', $parameters);
        }

        if (method_exists($class, '__construct')) {
            $rClass = new ReflectionClass($class);

            $instance = $rClass->newInstanceWithoutConstructor();

            if ($parameters !== []) {
                $dependencies = [];
                foreach ($parameters as $key => $value) {
                    if (is_string($key)) {
                        $dependencies[$key] = $value;
                    }
                }

                if ($dependencies !== []) {
                    $rMethod = $rClass->getMethod('__construct');
                    foreach ($rMethod->getParameters() as $rParameter) {
                        $name = $rParameter->getName();
                        unset($dependencies[$name]);
                    }

                    if ($dependencies !== []) {
                        $this->dependencies[$instance] = $dependencies;
                    }
                }
            }

            if ($instance instanceof Injectable) {
                $instance->setContainer($this);
            }

            $this->call([$instance, '__construct'], $parameters);
        } else {
            $instance = new $class();

            if ($parameters !== []) {
                $this->dependencies[$instance] = $parameters;
            }

            if ($instance instanceof Injectable) {
                $instance->setContainer($this);
            }
        }

        return $instance;
    }

    public function get(string $id): mixed
    {
        if (($instance = $this->instances[$id] ?? null) !== null) {
            return $instance;
        }

        $definition = $this->definitions[$id] ?? null;

        if ($definition === null) {
            if (preg_match('#^[\w\\\\]+$#', $id) !== 1) {
                throw new NotFoundException(["%s not found", $id]);
            }
            return $this->instances[$id] = $this->make($id);
        } elseif (is_string($definition)) {
            if ($definition[0] === '@') {
                return $this->get(substr($definition, 1));
            } elseif ($definition[0] === '#') {
                return $this->get("$id$definition");
            } elseif (str_contains($definition, '::')) {
                list($class, $method) = explode('::', $definition, 2);
                $obj = $this->get($class);
                return $this->instances[$id] = $this->call([$obj, $method], ['id' => $id]);
            } elseif (preg_match('#^[\w\\\\]+$#', $definition) !== 1) {
                return $this->get($definition);
            } else {
                return $this->instances[$id] = $this->make($definition);
            }
        } elseif ($definition instanceof Closure) {
            return $this->instances[$id] = $this->call($definition);
        } elseif (is_object($definition)) {
            return $this->instances[$id] = $definition;
        } elseif (is_array($definition)) {
            if (($class = $definition['class'] ?? null) !== null) {
                unset($definition['class']);
            } else {
                $class = $id;
            }

            return $this->instances[$id] = $this->make($class, $definition);
        } else {
            throw new NotSupportedException('not supported definition');
        }
    }

    protected function getClassUses(ReflectionClass $rClass): array
    {
        $short = $rClass->getShortName();
        $file = $rClass->getFileName();
        if (!is_string($file) || !str_ends_with($file, "$short.php")) {
            return [];
        }

        $str = file_get_contents($file);

        preg_match_all('#^use\s+(.+);#m', $str, $matches);

        $types = [];
        foreach ($matches[1] as $use) {
            $use = trim($use);

            if (str_contains($use, ' as ')) {
                list($full, , $short) = preg_split('#\s+#', $use, -1, PREG_SPLIT_NO_EMPTY);
                $types[$short] = $full;
            } else {
                if (($pos = strrpos($use, '\\')) !== false) {
                    $types[substr($use, $pos + 1)] = $use;
                } else {
                    $types[$use] = $use;
                }
            }
        }

        return $types;
    }

    protected function getTypes(string $class): array
    {
        $rClass = new ReflectionClass($class);
        $comment = $rClass->getDocComment();

        $uses = null;
        $types = [];
        if (is_string($comment)) {
            if (preg_match_all('#@property-read\s+([\w\\\\]+)\s+\\$(\w+)#m', $comment, $matches, PREG_SET_ORDER)
                > 0
            ) {
                foreach ($matches as list(, $type, $name)) {
                    if ($type === '\object') {
                        continue;
                    }

                    if ($type[0] === '\\') {
                        $types[$name] = substr($type, 1);
                    } else {
                        if ($uses === null) {
                            $uses = $this->getClassUses($rClass);
                        }

                        if (isset($uses[$type])) {
                            $types[$name] = $uses[$type];
                        } else {
                            $types[$name] = $rClass->getNamespaceName() . '\\' . $type;
                        }
                    }
                }
            }
        }

        $parent = get_parent_class($class);
        if ($parent !== false) {
            $types += $this->types[$parent] ?? $this->getTypes($parent);
        }
        return $this->types[$class] = $types;
    }

    public function inject(object $target, string $property): mixed
    {
        $class = get_class($target);

        $types = $this->types[$class] ?? $this->getTypes($class);
        if (($type = $types[$property] ?? null) === null) {
            throw new TypeHintException(['can\'t type-hint for `%s::%s`', $class, $property]);
        }

        $dependencies = $this->dependencies[$target] ?? null;
        $id = $dependencies[$property] ?? $dependencies[$type] ?? $type;

        return $target->$property = $this->get($id[0] === '#' ? "$type$id" : $id);
    }

    public function getDefinitions(): array
    {
        return $this->definitions;
    }

    public function getDefinition(string $id): mixed
    {
        return $this->definitions[$id] ?? null;
    }

    public function getInstances(): array
    {
        return $this->instances;
    }

    public function has(string $id): bool
    {
        if (isset($this->instances[$id])) {
            return true;
        } elseif (isset($this->definitions[$id])) {
            return true;
        } elseif (str_contains($id, '.')) {
            $glob = substr($id, 0, strrpos($id, '.')) . '.*';
            return isset($this->definitions[$glob]);
        } else {
            return interface_exists($id) || class_exists($id);
        }
    }

    public function call(callable $callable, array $parameters = []): mixed
    {
        if (is_array($callable)) {
            $rFunction = new ReflectionMethod($callable[0], $callable[1]);
        } else {
            $rFunction = new ReflectionFunction($callable);
        }

        $missing = [];
        $args = [];
        foreach ($rFunction->getParameters() as $position => $rParameter) {
            $name = $rParameter->getName();

            $rType = $rParameter->getType();
            $type = ($rType instanceof ReflectionNamedType && !$rType->isBuiltin()) ? $rType->getName() : null;

            if (array_key_exists($position, $parameters)) {
                $value = $parameters[$position];
            } elseif (array_key_exists($name, $parameters)) {
                $value = $parameters[$name];
            } elseif ($rParameter->isDefaultValueAvailable()) {
                $value = $rParameter->getDefaultValue();
            } elseif ($type !== null) {
                $value = $parameters[$type] ?? $type;
            } else {
                $missing[] = $name;
                continue;
            }

            if ($type !== null && is_string($value)) {
                if ($value[0] === '#') {
                    $value = $this->get("$type$value");
                } elseif ($value[0] === '@') {
                    $value = $this->get($value);
                } elseif (is_array($callable)) {
                    $object = $callable[0];
                    $dependencies = $this->dependencies[$object] ?? null;
                    $id = $dependencies[$name] ?? $dependencies[$type] ?? $type;

                    $value = $this->get($id[0] === '#' ? "$type$id" : $id);
                } else {
                    $value = $this->get($value);
                }
            }

            $args[] = $value;
        }

        if ($missing) {
            throw new MissingFieldException(implode(",", $missing));
        }

        return $callable(...$args);
    }
}
