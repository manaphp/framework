<?php

namespace ManaPHP\Cli;

use ManaPHP\Di\FactoryDefault;

/**
 * Class ManaPHP\Cli\Application
 *
 * @package application
 *
 * @property \ManaPHP\Cli\HandlerInterface $cliHandler
 */
class Application extends \ManaPHP\Application
{
    /** @noinspection MagicMethodsValidityInspection */
    /** @noinspection PhpMissingParentConstructorInspection */
    /**
     * Application constructor.
     *
     * @param \ManaPHP\Loader      $loader
     * @param \ManaPHP\DiInterface $di
     */
    public function __construct($loader, $di = null)
    {
        $calledClass = get_called_class();

        if ($calledClass === __CLASS__) {
            $this->_di = $di ?: new FactoryDefault();

            $this->_di->setShared('loader', $loader);
            $this->_di->setShared('application', $this);

            $appDir = dirname(get_included_files()[0]) . '/app';
            $appFile = $appDir . '/Application.php';
            if (is_file($appFile)) {
                $str = file_get_contents($appFile);
                if (preg_match('#namespace\s+([\w\\\\]+);#', $str, $match)) {
                    $this->loader->registerNamespaces([$match[1] => $appDir]);

                    $this->alias->set('@root', dirname($appDir));
                    $this->alias->set('@data', '@root/data');
                    $this->alias->set('@tmp', '@root/data/tmp');
                    $this->alias->set('@app', $appDir);
                    $this->alias->set('@ns.app', $match[1]);
                }
            }
        } else {
            parent::__construct($loader, $di);
        }

        if ($this->filesystem->dirExists('@app/Cli/Controllers')) {
            $this->alias->set('@cli', $this->alias->resolve('@app/Cli/Controllers'));
            $this->alias->set('@ns.cli', $this->alias->resolveNS(strtr('@app/Cli/Controllers', ['@app' => '@ns.app', '/' => '\\'])));
        } elseif ($calledClass !== __CLASS__ && $this->filesystem->dirExists('@app/Controllers')) {
            $this->alias->set('@cli', $this->alias->resolve('@app/Controllers'));
            $this->alias->set('@ns.cli', $this->alias->resolveNS(strtr('@app/Controllers', ['@app' => '@ns.app', '/' => '\\'])));
        }
    }

    public function registerServices()
    {
        $this->configure->bootstraps = array_diff($this->configure->bootstraps, ['debugger']);

        parent::registerServices();

        $this->_di->setShared('cliHandler', 'ManaPHP\Cli\Handler');
        $this->_di->setShared('console', 'ManaPHP\Cli\Console');
        $this->_di->setShared('arguments', 'ManaPHP\Cli\Arguments');
    }

    /**
     * @throws \ManaPHP\Configuration\Configure\Exception
     */
    public function main()
    {
        $this->registerServices();

        exit($this->cliHandler->handle());
    }
}