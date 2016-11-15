<?php

namespace ManaPHP\Mvc\Router;

use ManaPHP\Mvc\Router\Route\Exception as RouteException;
use ManaPHP\Utility\Text;

/**
 * Class ManaPHP\Mvc\Router\Route
 *
 * @package router
 */
class Route implements RouteInterface
{

    /**
     * @var string
     */
    protected $_pattern;

    /**
     * @var string
     */
    protected $_compiledPattern;

    /**
     * @var array
     */
    protected $_paths;

    /**
     * @var string
     */
    protected $_httpMethod;

    /**
     * \ManaPHP\Mvc\Router\Route constructor
     *
     * @param string       $pattern
     * @param string|array $paths
     * @param string       $httpMethod
     *
     * @throws \ManaPHP\Mvc\Router\Route\Exception
     */
    public function __construct($pattern, $paths = null, $httpMethod = null)
    {
        $this->_pattern = $pattern;
        $this->_compiledPattern = $this->_compilePattern($pattern);
        $this->_paths = self::getRoutePaths($paths);
        $this->_httpMethod = $httpMethod;
    }

    /**
     * Replaces placeholders from pattern returning a valid PCRE regular expression
     *
     * @param string $pattern
     *
     * @return string
     */
    protected function _compilePattern($pattern)
    {
        // If a pattern contains ':', maybe there are placeholders to replace
        if (Text::contains($pattern, ':')) {
            $tr = [
                '/:controller' => '/{controller:[a-z\d_-]+}',
                '/:action' => '/{action:[a-z\d_-]+}',
                '/:params' => '/{params:.+}',
                '/:int' => '/(\d+)',
            ];
            $pattern = strtr($pattern, $tr);
        }

        if (Text::contains($pattern, '{')) {
            $pattern = $this->_extractNamedParams($pattern);
        }

        if (Text::contains($pattern, '(') || Text::contains($pattern, '[')) {
            return '#^' . $pattern . '$#i';
        } else {
            return $pattern;
        }
    }

    /**
     * Extracts parameters from a string
     *
     * @param string $pattern
     *
     * @return string
     */
    protected function _extractNamedParams($pattern)
    {
        if (!Text::contains($pattern, '{')) {
            return $pattern;
        }

        $left_token = '@_@';
        $right_token = '!_!';
        $need_restore_token = false;

        if (preg_match('#{\d#', $pattern) === 1
            && !Text::contains($pattern, $left_token)
            && !Text::contains($pattern, $right_token)
        ) {
            $need_restore_token = true;
            $pattern = preg_replace('#{(\d+,?\d*)}#', $left_token . '\1' . $right_token, $pattern);
        }

        $matches = [];
        if (preg_match_all('#{([A-Z].*)}#Ui', $pattern, $matches, PREG_SET_ORDER) > 0) {
            foreach ($matches as $match) {

                if (!Text::contains($match[0], ':')) {
                    $to = '(?<' . $match[1] . '>[\w-]+)';
                    $pattern = str_replace($match[0], $to, $pattern);
                } else {
                    $parts = explode(':', $match[1]);
                    $to = '(?<' . $parts[0] . '>' . $parts[1] . ')';
                    $pattern = str_replace($match[0], $to, $pattern);
                }
            }
        }

        if ($need_restore_token) {
            $from = [$left_token, $right_token];
            $to = ['{', '}'];
            $pattern = str_replace($from, $to, $pattern);
        }

        return $pattern;
    }

    /**
     * Returns routePaths
     *
     * @param string|array $paths
     *
     * @return array
     * @throws \ManaPHP\Mvc\Router\Route\Exception
     */
    public static function getRoutePaths($paths = null)
    {
        $routePaths = [];

        if (is_string($paths)) {
            $parts = explode('::', $paths);
            if (count($parts) === 2) {
                $routePaths['controller'] = $parts[0];
                /** @noinspection MultiAssignmentUsageInspection */
                $routePaths['action'] = $parts[1];
            } else {
                $routePaths['controller'] = $parts[0];
            }
        } elseif (is_array($paths)) {
            if (isset($paths[0])) {
                if (strpos($paths[0], '::')) {
                    $parts = explode('::', $paths[0]);
                    $routePaths['controller'] = $parts[0];
                    $routePaths['action'] = $parts[1];
                } else {
                    $routePaths['controller'] = $paths[0];
                }
            }

            if (isset($paths[1])) {
                $routePaths['action'] = $paths[1];
            }

            foreach ($paths as $k => $v) {
                if (is_string($k)) {
                    $routePaths[$k] = $v;
                }
            }
        }

        if (isset($routePaths['controller']) && is_string($routePaths['controller'])) {
            $parts = explode('\\', $routePaths['controller']);
            $routePaths['controller'] = basename(end($parts), 'Controller');
        }

        return $routePaths;
    }

    /**
     * Returns the paths
     *
     * @return array
     */
    public function getPaths()
    {
        return $this->_paths;
    }

    /**
     * @param string $uri
     *
     * @return bool|array
     * @throws \ManaPHP\Mvc\Router\Route\Exception
     */
    public function match($uri)
    {
        $matches = [];

        if ($this->_httpMethod !== null && $this->_httpMethod !== $_SERVER['REQUEST_METHOD']) {
            return false;
        }

        if (Text::contains($this->_compiledPattern, '^')) {
            $r = preg_match($this->_compiledPattern, $uri, $matches);
            if ($r === false) {
                throw new RouteException('`:compiled` pcre pattern is invalid for `:pattern`'/**m0d6fa1de6a93475dd*/,
                    ['compiled' => $this->_compiledPattern, 'pattern' => $this->_pattern]);
            } elseif ($r === 1) {
                return $matches;
            } else {
                return false;
            }
        } else {
            if ($this->_compiledPattern === $uri) {
                return $matches;
            } else {
                return false;
            }
        }
    }
}