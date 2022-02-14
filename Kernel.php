<?php
declare(strict_types=1);

namespace ManaPHP;

use ManaPHP\Di\Container;
use ManaPHP\Di\ContainerInterface;

/**
 * @property-read \ManaPHP\EnvInterface    $env
 * @property-read \ManaPHP\ConfigInterface $config
 * @property-read \ManaPHP\AliasInterface  $alias
 */
class Kernel extends Component
{
    protected string $rootDir;

    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;

        $container = new Container();
        $this->setContainer($container);

        $GLOBALS['ManaPHP\Di\ContainerInterface'] = $container;

        if (!defined('MANAPHP_COROUTINE_ENABLED')) {
            define(
                'MANAPHP_COROUTINE_ENABLED', PHP_SAPI === 'cli'
                && extension_loaded('swoole')
                && !extension_loaded('xdebug')
            );
        }

        $this->alias->set('@public', "$rootDir/public");
        $this->alias->set('@app', "$rootDir/app");
        $this->alias->set('@views', "$rootDir/app/Views");
        $this->alias->set('@root', $rootDir);
        $this->alias->set('@data', "$rootDir/data");
        $this->alias->set('@tmp', "$rootDir/tmp");
        $this->alias->set('@resources', "$rootDir/Resources");
        $this->alias->set('@config', "$rootDir/config");
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    public function start(string $server): void
    {
        $this->env->load();
        $this->config->load();

        if (($timezone = $this->config->get('timezone', '')) !== '') {
            date_default_timezone_set($timezone);
        }

        foreach ($this->config->get('aliases', []) as $k => $v) {
            $this->alias->set($k, $v);
        }

        foreach ($this->config->get('dependencies') as $id => $definition) {
            $this->container->set($id, $definition);
        }

        foreach ($this->config->get('bootstrappers') as $item) {
            /** @var \ManaPHP\BootstrapperInterface $bootstrapper */
            $bootstrapper = $this->container->get($item);
            $bootstrapper->bootstrap();
        }

        $this->container->get($server)->start();
    }
}