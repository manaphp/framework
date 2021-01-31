<?php

namespace ManaPHP\Di;

use Closure;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;
use ReflectionClass;

class Container implements ContainerInterface
{
    /**
     * @var array
     */
    protected $_definitions = [];

    /**
     * @var array
     */
    protected $_instances = [];

    /**
     * @var \ManaPHP\Di\ContainerInterface
     */
    protected static $_default;

    public function __construct()
    {
        if (self::$_default === null) {
            self::$_default = $this;
        }
    }

    /**
     * @return static
     */
    public static function getDefault()
    {
        return self::$_default;
    }

    /**
     * @param string $name
     * @param string $className
     *
     * @return string
     */
    protected function _completeClassName($name, $className)
    {
        if (isset($this->_definitions[$name])) {
            $definition = $this->_definitions[$name];
        } else {
            return $className;
        }

        if (is_string($definition)) {
            if ($pos = strrpos($definition, '\\')) {
                return substr($definition, 0, $pos + 1) . ucfirst($className);
            } else {
                return $className;
            }
        } elseif (is_array($definition) && isset($definition['class'])) {
            if ($pos = strrpos($definition['class'], '\\')) {
                return substr($definition['class'], 0, $pos + 1) . ucfirst($className);
            } else {
                return $className;
            }
        } else {
            return $className;
        }
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function _inferClassName($name)
    {
        $definition = null;
        if (isset($this->_definitions[$name])) {
            $definition = $this->_definitions[$name];
        } elseif (str_contains($name, '\\')) {
            $definition = $name;
        } elseif ($pos = strrpos($name, '_')) {
            $maybe = substr($name, $pos + 1);
            if (isset($this->_definitions[$maybe])) {
                $definition = $this->_definitions[$maybe];
            } elseif ($pos = strpos($name, '_')) {
                $maybe = substr($name, 0, $pos);
                if (isset($this->_definitions[$maybe])) {
                    $definition = $this->_definitions[$maybe];
                }
            }
        } elseif (preg_match('#^(.+)([A-Z].+?)$#', $name, $match)) {
            $maybe = lcfirst($match[2]);
            $definition = $this->_definitions[$maybe] ?? null;
        }

        if ($definition === null) {
            throw new InvalidValueException(['`%s` definition is invalid: missing class field', $name]);
        } elseif (is_string($definition)) {
            return $definition[0] === '@' ? $this->_inferClassName(substr($definition, 1)) : $definition;
        } else {
            return $definition['class'];
        }
    }

    /**
     * Registers a component in the components container
     *
     * @param string $name
     * @param mixed  $definition
     *
     * @return static
     */
    public function set($name, $definition)
    {
        if (is_string($definition)) {
            if (str_contains($definition, '/') || preg_match('#^[\w\\\\]+$#', $definition) !== 1) {
                $definition = ['class' => $this->_inferClassName($name), $definition, 'shared' => false];
            } else {
                if (!str_contains($definition, '\\')) {
                    $definition = $this->_completeClassName($name, $definition);
                }
                $definition = ['class' => $definition, 'shared' => false];
            }
        } elseif (is_array($definition)) {
            if (isset($definition['class'])) {
                if (!str_contains($definition['class'], '\\')) {
                    $definition['class'] = $this->_completeClassName($name, $definition['class']);
                }
            } elseif (isset($definition[0]) && count($definition) !== 1) {
                if (!str_contains($definition[0], '\\')) {
                    $definition[0] = $this->_completeClassName($name, $definition[0]);
                }
            } else {
                $definition['class'] = $this->_inferClassName($name);
            }

            $definition['shared'] = false;
        } elseif (is_object($definition)) {
            $definition = ['class' => $definition, 'shared' => !$definition instanceof Closure];
        } else {
            throw new NotSupportedException(['`:definition` definition is unknown', 'definition' => $name]);
        }

        $this->_definitions[$name] = $definition;

        return $this;
    }

    /**
     * Registers an "always shared" component in the components container
     *
     * @param string $name
     * @param mixed  $definition
     *
     * @return static
     */
    public function setShared($name, $definition)
    {
        if (isset($this->_instances[$name])) {
            throw new MisuseException(['it\'s too late to setShared(): `%s` instance has been created', $name]);
        }

        if (is_string($definition)) {
            if ($definition[0] === '@') {
                null;
            } elseif (str_contains($definition, '/') || preg_match('#^[\w\\\\]+$#', $definition) !== 1) {
                $definition = ['class' => $this->_inferClassName($name), $definition];
            } elseif (!str_contains($definition, '\\')) {
                $definition = $this->_completeClassName($name, $definition);
            }
        } elseif (is_array($definition)) {
            if (isset($definition['class'])) {
                if (!str_contains($definition['class'], '\\')) {
                    $definition['class'] = $this->_completeClassName($name, $definition['class']);
                }
            } elseif (isset($definition[0]) && count($definition) !== 1) {
                if (!str_contains($definition[0], '\\')) {
                    $definition[0] = $this->_completeClassName($name, $definition[0]);
                }
            } else {
                $definition['class'] = $this->_inferClassName($name);
            }
        } elseif ($definition instanceof Closure) {
            null;
        } elseif (is_object($definition)) {
            $this->_instances[$name] = $definition;
        } else {
            throw new NotSupportedException(['`:definition` definition is unknown', 'definition' => $name]);
        }

        $this->_definitions[$name] = $definition;

        return $this;
    }

    /**
     * Removes a component in the components container
     *
     * @param string $name
     *
     * @return static
     */
    public function remove($name)
    {
        unset($this->_definitions[$name], $this->_instances[$name], $this->{$name});

        return $this;
    }

    /**
     * Resolves the component based on its configuration
     *
     * @param string $name
     * @param array  $parameters
     *
     * @return mixed
     */
    public function getNew($name, $parameters = [])
    {
        $definition = $this->_definitions[$name] ?? $name;

        if ($parameters && !isset($parameters[0])) {
            $parameters = [$parameters];
        }

        if (is_string($definition)) {
            return $this->_createNew($name, $definition, $parameters);
        } elseif ($definition instanceof Closure) {
            $instance = $definition(...$parameters);
        } elseif (is_object($definition)) {
            $instance = $definition;
        } else {
            throw new NotSupportedException(['`%s` component cannot be resolved', $name]);
        }

        if ($instance instanceof Injectable) {
            $instance->setContainer($this);
        }

        return $instance;
    }

    /**
     * @param string $name
     * @param string $class
     * @param array  $parameters
     *
     * @return mixed
     */
    protected function _createNew($name, $class, $parameters)
    {
        if (!class_exists($class)) {
            throw new InvalidValueException(
                ['`%s` component cannot be resolved: `%s` class is not exists', $name, $class]
            );
        }

        if (method_exists($class, '__construct')) {
            $rc = new ReflectionClass($class);

            $instance = $rc->newInstanceWithoutConstructor();
            if ($instance instanceof Injectable) {
                $instance->setContainer($this);
            }
            /** @noinspection PhpPossiblePolymorphicInvocationInspection */
            $instance->__construct(...$parameters);
        } else {
            $instance = new $class(...$parameters);

            if ($instance instanceof Injectable) {
                $instance->setContainer($this);
            }
        }

        return $instance;
    }

    /**
     * Resolves a component, the resolved component is stored in the DI, subsequent requests for this component will
     * return the same instance
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getShared($name)
    {
        if ($instance = $this->_instances[$name] ?? null) {
            return $instance;
        }

        $definition = $this->_definitions[$name] ?? $name;

        if (is_string($definition)) {
            if ($definition[0] === '@') {
                return $this->_instances[$name] = $this->getShared(substr($definition, 1));
            }
            $parameters = [];
        } elseif ($definition instanceof Closure) {
            $instance = $definition();
            if ($instance instanceof Injectable) {
                $instance->setContainer($this);
            }

            return $this->_instances[$name] = $instance;
        } elseif (isset($definition['class'])) {
            $parameters = $definition;
            $definition = $definition['class'];
            unset($parameters['class']);
        } elseif (isset($definition[0])) {
            $parameters = $definition;
            $definition = $definition[0];
            unset($parameters[0]);
        } else {
            $parameters = [];
        }

        if ($parameters && !isset($parameters[0])) {
            $parameters = [$parameters];
        }

        if (!is_string($definition)) {
            throw new NotSupportedException(['`%s` component implement type is not supported', $name]);
        }

        $definition = $this->_definitions[$definition] ?? $definition;

        return $this->_instances[$name] = $this->_createNew($name, $definition, $parameters);
    }

    /**
     * @param string $pattern
     *
     * @return array
     */
    public function getDefinitions($pattern = null)
    {
        if ($pattern === null) {
            return $this->_definitions;
        } else {
            $definitions = [];
            foreach ($this->_definitions as $name => $definition) {
                if (fnmatch($pattern, $name)) {
                    $definitions[$name] = $definition;
                }
            }
            return $definitions;
        }
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getDefinition($name)
    {
        return $this->_definitions[$name] ?? null;
    }

    /**
     * @return array
     */
    public function getInstances()
    {
        return $this->_instances;
    }

    /**
     * Magic method __get
     *
     * @param string $propertyName
     *
     * @return mixed
     */
    public function __get($propertyName)
    {
        return $this->getShared($propertyName);
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        if ($value === null) {
            $this->remove($name);
        } else {
            $this->setShared($name, $value);
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return $this->has($name);
    }

    /**
     * Check whether the DI contains a component by a name
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return isset($this->_definitions[$name]);
    }
}
