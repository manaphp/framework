<?php
namespace ManaPHP\Http;

use ManaPHP\Http\Server\HandlerInterface;

/**
 * Class Application
 * @property-read \ManaPHP\Http\ServerInterface   $httpServer
 * @property-read \ManaPHP\Http\RequestInterface  $request
 * @property-read \ManaPHP\Http\ResponseInterface $response
 * @property-read \ManaPHP\RouterInterface        $router
 * @property-read \ManaPHP\DispatcherInterface    $dispatcher
 * @property-read \ManaPHP\ViewInterface          $view
 * @property-read \ManaPHP\Http\SessionInterface  $session
 *
 * @package ManaPHP\Http
 * @method void authorize()
 */
abstract class Application extends \ManaPHP\Application implements HandlerInterface
{
    public function __construct($loader = null)
    {
        if (!defined('MANAPHP_COROUTINE')) {
            define('MANAPHP_COROUTINE', PHP_SAPI === 'cli' && class_exists('Swoole\Runtime') && !class_exists('Workerman\Worker'));
        }

        parent::__construct($loader);

        $this->eventsManager->attachEvent('request:begin', [$this, 'generateRequestId']);
        $this->eventsManager->attachEvent('request:authenticate', [$this, 'authenticate']);

        if (method_exists($this, 'authorize')) {
            $this->eventsManager->attachEvent('request:authorize', [$this, 'authorize']);
        }

        if (PHP_SAPI === 'cli') {
            if (class_exists('Workerman\Worker')) {
                $this->getDi()->setShared('httpServer', 'ManaPHP\Http\Server\Adapter\Workerman');
            } elseif (extension_loaded('swoole')) {
                $this->getDi()->setShared('httpServer', 'ManaPHP\Http\Server\Adapter\Swoole');
            } else {
                $this->getDi()->setShared('httpServer', 'ManaPHP\Http\Server\Adapter\Php');
            }
        } elseif (PHP_SAPI === 'cli-server') {
            $this->getDi()->setShared('httpServer', 'ManaPHP\Http\Server\Adapter\Php');
        } else {
            $this->getDi()->setShared('httpServer', 'ManaPHP\Http\Server\Adapter\Fpm');
        }
    }

    public function authenticate()
    {
        $this->identity->authenticate();
    }

    public function generateRequestId()
    {
        if (!$this->request->hasServer('HTTP_X_REQUEST_ID')) {
            $globals = $this->request->getGlobals();

            $globals->_SERVER['HTTP_X_REQUEST_ID'] = 'aa' . bin2hex(random_bytes(15));
        }
    }

    abstract public function handle();

    public function main()
    {
        $this->dotenv->load();
        $this->configure->load();

        $this->registerServices();

        $this->httpServer->start($this);
    }
}