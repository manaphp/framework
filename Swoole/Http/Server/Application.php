<?php
namespace ManaPHP\Swoole\Http\Server;

use ManaPHP\Http\Response;
use ManaPHP\Rest\Factory;
use ManaPHP\Router\NotFoundRouteException;

/**
 * Class ManaPHP\Mvc\Application
 *
 * @package application
 * @property-read \ManaPHP\Http\RequestInterface       $request
 * @property-read \ManaPHP\Http\ResponseInterface      $response
 * @property-read \ManaPHP\RouterInterface             $router
 * @property-read \ManaPHP\Mvc\DispatcherInterface     $dispatcher
 * @property-read \ManaPHP\Http\SessionInterface       $session
 * @property-read \ManaPHP\Swoole\Http\ServerInterface $swooleHttpServer
 */
class Application extends \ManaPHP\Application
{
    /**
     * Application constructor.
     *
     * @param \ManaPHP\Loader $loader
     */
    public function __construct($loader = null)
    {
        ini_set('html_errors', 'off');
        parent::__construct($loader);
        $routerClass = $this->alias->resolveNS('@ns.app\Router');
        if (class_exists($routerClass)) {
            $this->_di->setShared('router', $routerClass);
        }
    }

    public function getDi()
    {
        if (!$this->_di) {
            $this->_di = new Factory();
            $this->_di->setShared('response', '\ManaPHP\Swoole\Http\Server\Response');
            $this->_di->setShared('swooleHttpServer', 'ManaPHP\Swoole\Http\Server');
            $this->_di->keepInstanceState(true);
        }

        return $this->_di;
    }

    public function authenticate()
    {

    }

    public function handle()
    {
        try {
            $this->authenticate();

            if (!$this->router->match()) {
                throw new NotFoundRouteException(['router does not have matched route for `:uri`', 'uri' => $this->router->getRewriteUri()]);
            }

            $controller = $this->router->getController();
            $action = $this->router->getAction();
            $params = $this->router->getParams();

            $this->dispatcher->dispatch($controller, $action, $params);
            $actionReturnValue = $this->dispatcher->getReturnedValue();
            if ($actionReturnValue !== null && !$actionReturnValue instanceof Response) {
                $this->response->setJsonContent($actionReturnValue);
            }
        } catch (\Exception $exception) {
            $this->handleException($exception);
        } catch (\Error $error) {
            $this->handleException($error);
        }

        $this->response->send();
        $this->_di->restoreInstancesState();
    }

    public function main()
    {
        $this->dotenv->load();
        $this->configure->load();

        $this->registerServices();

        $this->swooleHttpServer->start([$this, 'handle']);
    }
}