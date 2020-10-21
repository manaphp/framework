<?php

namespace ManaPHP\Http;

use ManaPHP\Http\Server\HandlerInterface;

/**
 * Class Application
 *
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
        parent::__construct($loader);

        $this->attachEvent('request:authenticate', [$this, 'authenticate']);

        if (method_exists($this, 'authorize')) {
            $this->attachEvent('request:authorize', [$this, 'authorize']);
        }

        if ($_SERVER['DOCUMENT_ROOT'] === '') {
            $_SERVER['DOCUMENT_ROOT'] = dirname($_SERVER['SCRIPT_FILENAME']);
        }
    }

    public function authenticate()
    {
        $this->identity->authenticate();
    }

    abstract public function handle();

    public function main()
    {
        $this->dotenv->load();
        $this->configure->load();

        $this->registerConfigure();

        $this->httpServer->start($this);
    }
}