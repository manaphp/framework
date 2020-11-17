<?php

namespace ManaPHP;

use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Exception\MisuseException;

class Alias extends Component implements AliasInterface
{
    /**
     * @var array
     */
    protected $_aliases = [];

    public function __construct()
    {
        $this->set('@manaphp', __DIR__);
    }

    /**
     * @param string $name
     * @param string $path
     *
     * @return string
     */
    public function set($name, $path)
    {
        if ($name[0] !== '@') {
            throw new MisuseException(['`:name` must start with `@`', 'name' => $name]);
        }

        if ($path === '') {
            $this->_aliases[$name] = $path;
        } elseif ($path[0] !== '@') {
            if (DIRECTORY_SEPARATOR === '/' || str_starts_with($name, '@ns.')) {
                $this->_aliases[$name] = $path;
            } else {
                $this->_aliases[$name] = strtr($path, '\\', '/');
            }
        } else {
            $this->_aliases[$name] = $this->resolve($path);
        }

        return $this->_aliases[$name];
    }

    /**
     * @param string $name
     *
     * @return string|array|null
     */
    public function get($name = null)
    {
        if ($name === null) {
            return $this->_aliases;
        }

        if ($name[0] !== '@') {
            throw new MisuseException(['`:name` must start with `@`', 'name' => $name]);
        }

        return $this->_aliases[$name] ?? null;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        if ($name[0] !== '@') {
            throw new MisuseException(['`:name` must start with `@`', 'name' => $name]);
        }

        return isset($this->_aliases[$name]);
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function resolve($path)
    {
        if ($path[0] !== '@') {
            return DIRECTORY_SEPARATOR === '/' ? $path : strtr($path, '\\', '/');
        }

        if (str_contains($path, '{') && preg_match_all('#{([^}]+)}#', $path, $matches)) {
            foreach ((array)$matches[1] as $k => $match) {
                if (is_numeric($match)) {
                    $replaced = substr(bin2hex(random_bytes($match / 2 + 1)), 0, $match);
                } else {
                    $ts = $ts ?? time();
                    $replaced = date($match, $ts);
                }

                $path = str_replace($matches[0][$k], $replaced, $path);
            }
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            $path = strtr($path, '\\', '/');
        }

        if (($pos = strpos($path, '/')) === false) {
            if (!isset($this->_aliases[$path])) {
                throw new InvalidArgumentException(['`:alias` is not exists', 'alias' => $path]);
            }
            return $this->_aliases[$path];
        }

        $alias = substr($path, 0, $pos);

        if (!isset($this->_aliases[$alias])) {
            throw new InvalidArgumentException(['`%s` is not exists for `%s`', $alias, $path]);
        }

        return $this->_aliases[$alias] . substr($path, $pos);
    }

    /**
     * @param string $ns
     *
     * @return string
     */
    public function resolveNS($ns)
    {
        if ($ns[0] !== '@') {
            return $ns;
        }

        $parts = explode('\\', $ns, 2);

        $alias = $parts[0];
        if (!isset($this->_aliases[$alias])) {
            throw new InvalidArgumentException(['`%s` is not exists for `%s`', $alias, $ns]);
        }

        return $this->_aliases[$alias] . (isset($parts[1]) ? '\\' . $parts[1] : '');
    }
}