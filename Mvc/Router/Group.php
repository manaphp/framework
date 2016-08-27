<?php

namespace ManaPHP\Mvc\Router;

/**
 * ManaPHP\Mvc\Router\Group
 *
 * Helper class to create a group of routes with common attributes
 *
 * NOTE_PHP:
 *        1.Hostname Constraints has been removed by PHP implementation
 *        2. remove clear method
 *
 *<code>
 * $router = new ManaPHP\Mvc\Router();
 *
 * //Create a group with a common module and controller
 * $blog = new ManaPHP\Mvc\Router\Group(array(
 *    'module' => 'blog',
 *    'controller' => 'index'
 * ));
 *
 * //All the routes start with /blog
 * $blog->setPrefix('/blog');
 *
 * //Add a route to the group
 * $blog->add('/save', array(
 *    'action' => 'save'
 * ));
 *
 * //Add another route to the group
 * $blog->add('/edit/{id}', array(
 *    'action' => 'edit'
 * ));
 *
 * //This route maps to a controller different than the default
 * $blog->add('/blog', array(
 *    'controller' => 'about',
 *    'action' => 'index'
 * ));
 *
 * //Add the group to the router
 * $router->mount($blog);
 *</code>
 *
 */
class Group implements GroupInterface
{
    /**
     * @var \ManaPHP\Mvc\Router\RouteInterface[]
     */
    protected $_routes = [];

    /**
     * @var boolean
     */
    protected $_useDefaultRoutes;

    public function __construct($useDefaultRoutes = true)
    {
        $this->_useDefaultRoutes = $useDefaultRoutes;
    }

    /**
     * Adds a route applying the common attributes
     *
     * @param string       $pattern
     * @param string|array $paths
     * @param string       $httpMethod
     *
     * @return \ManaPHP\Mvc\Router\RouteInterface
     * @throws \ManaPHP\Mvc\Router\Exception
     */
    protected function _addRoute($pattern, $paths = null, $httpMethod = null)
    {
        $route = new Route($pattern, $paths, $httpMethod);
        $this->_routes[] = $route;

        return $route;
    }

    /**
     * Adds a route to the router on any HTTP method
     *
     *<code>
     * $router->add('/about', 'About::index');
     *</code>
     *
     * @param string       $pattern
     * @param string|array $paths
     * @param string|array $httpMethod
     *
     * @return \ManaPHP\Mvc\Router\RouteInterface
     * @throws \ManaPHP\Mvc\Router\Exception
     */
    public function add($pattern, $paths = null, $httpMethod = null)
    {
        return $this->_addRoute($pattern, $paths, $httpMethod);
    }

    /**
     * Adds a route to the router that only match if the HTTP method is GET
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Mvc\Router\RouteInterface
     * @throws \ManaPHP\Mvc\Router\Exception
     */
    public function addGet($pattern, $paths = null)
    {
        return $this->_addRoute($pattern, $paths, 'GET');
    }

    /**
     * Adds a route to the router that only match if the HTTP method is POST
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Mvc\Router\RouteInterface
     * @throws \ManaPHP\Mvc\Router\Exception
     */
    public function addPost($pattern, $paths = null)
    {
        return $this->_addRoute($pattern, $paths, 'POST');
    }

    /**
     * Adds a route to the router that only match if the HTTP method is PUT
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Mvc\Router\RouteInterface
     * @throws \ManaPHP\Mvc\Router\Exception
     */
    public function addPut($pattern, $paths = null)
    {
        return $this->_addRoute($pattern, $paths, 'PUT');
    }

    /**
     * Adds a route to the router that only match if the HTTP method is PATCH
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Mvc\Router\RouteInterface
     * @throws \ManaPHP\Mvc\Router\Exception
     */
    public function addPatch($pattern, $paths = null)
    {
        return $this->_addRoute($pattern, $paths, 'PATCH');
    }

    /**
     * Adds a route to the router that only match if the HTTP method is DELETE
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Mvc\Router\RouteInterface
     * @throws \ManaPHP\Mvc\Router\Exception
     */
    public function addDelete($pattern, $paths = null)
    {
        return $this->_addRoute($pattern, $paths, 'DELETE');
    }

    /**
     * Add a route to the router that only match if the HTTP method is OPTIONS
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Mvc\Router\RouteInterface
     * @throws \ManaPHP\Mvc\Router\Exception
     */
    public function addOptions($pattern, $paths = null)
    {
        return $this->_addRoute($pattern, $paths, 'OPTIONS');
    }

    /**
     * Adds a route to the router that only match if the HTTP method is HEAD
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Mvc\Router\RouteInterface
     * @throws \ManaPHP\Mvc\Router\Exception
     */
    public function addHead($pattern, $paths = null)
    {
        return $this->_addRoute($pattern, $paths, 'HEAD');
    }

    /**
     * @param string $uri
     *
     * @return array|false
     */
    public function match($uri)
    {
        for ($i = count($this->_routes) - 1; $i >= 0; $i--) {
            $route = $this->_routes[$i];

            $matches = $route->match($uri);
            if ($matches !== false) {
                $parts = [];

                /** @noinspection ForeachSourceInspection */
                foreach ($matches as $k => $v) {
                    if (is_string($k)) {
                        $parts[$k] = $v;
                    }
                }

                foreach ($route->getPaths() as $k => $v) {
                    if (is_int($v)) {
                        if (isset($matches[$v])) {
                            $parts[$k] = $matches[$v];
                        }
                    } else {
                        $parts[$k] = $v;
                    }
                }

                return $parts;
            }
        }

        if ($this->_useDefaultRoutes) {

            $paths = [];

            if ($uri === '/') {
                return $paths;
            }

            $parts = explode('/', trim($uri, '/'), 3);
            $count = count($parts);
            if ($count === 1) {
                $paths['controller'] = $parts[0];
            } elseif ($count === 2) {
                $paths['controller'] = $parts[0];
                $paths['action'] = $parts[1];
            } elseif ($count === 3) {
                $paths['controller'] = $parts[0];
                $paths['action'] = $parts[1];
                $paths['params'] = $parts[2];
            }

            if (isset($paths['controller']) && preg_match('#^[a-zA-Z0-9_-]+$#', $paths['controller']) !== 1) {
                return false;
            }

            if (isset($paths['action']) && preg_match('#^[a-zA-Z0-9_-]+$#', $paths['action']) !== 1) {
                return false;
            }

            return $paths;
        } else {
            return false;
        }
    }
}