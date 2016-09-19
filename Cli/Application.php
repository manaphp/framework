<?php
namespace ManaPHP\Cli;

use ManaPHP\Di\FactoryDefault;
use ManaPHP\Utility\Text;

/**
 * Class Application
 *
 * @package ManaPHP\Cli
 *
 * @property \ManaPHP\Cli\ConsoleInterface $console
 *
 */
abstract class Application extends \ManaPHP\Application
{
    /**
     * @var array
     */
    protected $_args;

    /**
     * @var array
     */
    protected $_controllerAliases = [];

    public function __construct($dependencyInjector = null)
    {
        $this->_dependencyInjector = $dependencyInjector ?: new FactoryDefault();

        $this->_dependencyInjector->setShared('application', $this);
        $this->_dependencyInjector->setShared('console', 'ManaPHP\Cli\Console');
        $this->_dependencyInjector->setShared('arguments', 'ManaPHP\Cli\Arguments');
        $this->_dependencyInjector->setShared('crossword', 'ManaPHP\Text\Crossword');
    }

    /**
     * @param array $args
     *
     * @return int
     * @throws \ManaPHP\Cli\Application\Exception
     */
    public function handle($args = null)
    {
        $this->_args = $args ?: $GLOBALS['argv'];
        if (count($this->_args) === 1) {
            $command = 'help:list';
        } else {
            $command = $this->_args[1];
        }

        $parts = explode(':', $command);
        switch (count($parts)) {
            case 1:
                $controllerName = $parts[0];
                $actionName = 'default';
                break;
            case 2:
                $controllerName = $parts[0];
                $actionName = $parts[1];
                break;
            default:
                $this->console->writeLn('command name is invalid: ' . $command);
                return 1;
        }

        $r = $this->dispatch($controllerName, $actionName);
        return is_int($r) ? $r : 0;
    }

    /**
     * @param string $controller
     * @param string $action
     *
     * @return int
     * @throws \ManaPHP\Cli\Application\Exception
     */
    public function dispatch($controller, $action)
    {
        $controllerName = Text::camelize($controller);
        $actionName = lcfirst(Text::camelize($action));

        $controllerClassName = null;
        foreach (['Application\\Cli\\Controllers\\' . $controllerName . 'Controller', 'ManaPHP\\Cli\\Controllers\\' . $controllerName . 'Controller'] as $class) {
            if ($this->_dependencyInjector->has($class) || class_exists($class)) {
                $controllerClassName = $class;
            }
        }

        if (!$controllerClassName) {
            $this->console->writeLn('``:command` command is not exists'/**m0d7fa39c3a64b91e0*/, ['command' => lcfirst($controllerName) . ':' . $actionName]);
            return 1;
        }

        $controllerInstance = $this->_dependencyInjector->getShared($controllerClassName);

        $actionMethod = $actionName . 'Action';
        if (!method_exists($controllerInstance, $actionMethod)) {
            $this->console->writeLn('`:command` sub command is not exists'/**m061a35fc1c0cd0b6f*/, ['command' => lcfirst($controllerName) . ':' . $actionName]);
            return 1;
        }

        // Calling beforeExecuteRoute as callback
        if (method_exists($controllerInstance, 'beforeExecuteRoute')) {
            if ($controllerInstance->beforeExecuteRoute($this) === false) {
                return 0;
            }
        }

        $r = $controllerInstance->$actionMethod();

        if (method_exists($controllerInstance, 'afterExecuteRoute')) {
            if ($controllerInstance->afterExecuteRoute($this) === false) {
                return 0;
            }
        }

        return $r;
    }
}