<?php

namespace ManaPHP;

use ManaPHP\Alias\Exception as AliasException;

/**
 * Class ManaPHP\Alias
 *
 * @package alias
 */
class Alias extends Component implements AliasInterface
{
    /**
     * @var array
     */
    protected $_aliases = [];

    /**
     * Alias constructor.
     *
     * @throws \ManaPHP\Alias\Exception
     */
    public function __construct()
    {
        $this->set('@manaphp', __DIR__);
    }

    /**
     * @param string $name
     * @param string $path
     *
     * @return string
     * @throws \ManaPHP\Alias\Exception
     */
    public function set($name, $path)
    {
        if ($name[0] !== '@') {
            throw new AliasException('`:name` must start with `@`'/**m02b52e71dba71561a*/, ['name' => $name]);
        }

        if ($path === '') {
            $this->_aliases[$name] = $path;
        } elseif ($path[0] !== '@') {
            $this->_aliases[$name] = (strpos($name, '@ns.') === 0 || DIRECTORY_SEPARATOR === '/') ? $path : strtr($path, '\\', '/');
        } else {
            $this->_aliases[$name] = $this->resolve($path);
        }

        return $this->_aliases[$name];
    }

    /**
     * @param string $name
     *
     * @return bool|string|array
     * @throws \ManaPHP\Alias\Exception
     */
    public function get($name = null)
    {
        if ($name === null) {
            return $this->_aliases;
        }

        if ($name[0] !== '@') {
            throw new AliasException('`:name` must start with `@`'/**m0f809631289d02f8e*/, ['name' => $name]);
        }

        return isset($this->_aliases[$name]) ? $this->_aliases[$name] : false;
    }

    /**
     * @param string $name
     *
     * @return bool
     * @throws \ManaPHP\Alias\Exception
     */
    public function has($name)
    {
        if ($name[0] !== '@') {
            throw new AliasException('`:name` must start with `@`'/**m0f7f21386c79f1518*/, ['name' => $name]);
        }

        return isset($this->_aliases[$name]);
    }

    /**
     * @param string $path
     *
     * @return string
     * @throws \ManaPHP\Alias\Exception
     */
    public function resolve($path)
    {
        if ($path[0] !== '@') {
            return DIRECTORY_SEPARATOR === '/' ? $path : strtr($path, '\\', '/');
        }

        if (strpos($path, '@ns.') === 0) {
            $parts = explode('\\', $path, 2);
        } else {
            $path = strtr($path, '\\', '/');
            $parts = explode('/', $path, 2);
        }

        $alias = $parts[0];
        if (!isset($this->_aliases[$alias])) {
            throw new AliasException('`:alias` is not exists for `:path`'/**m0aac421937afe5850*/, ['alias' => $alias, 'path' => $path]);
        }

        return str_replace($alias, $this->_aliases[$alias], $path);
    }

    /**
     * @param string $ns
     *
     * @return string
     * @throws \ManaPHP\Alias\Exception
     */
    public function resolveNS($ns)
    {
        if ($ns[0] !== '@') {
            return $ns;
        }

        $parts = explode('\\', $ns, 2);

        $alias = $parts[0];
        if (!isset($this->_aliases[$alias])) {
            throw new AliasException('`:alias` is not exists for `:namespace`'/**m0aac421937afe5850*/, ['alias' => $alias, 'namespace' => $ns]);
        }

        return $this->_aliases[$alias] . (isset($parts[1]) ? '\\' . $parts[1] : '');
    }
}