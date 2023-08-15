<?php
declare(strict_types=1);

namespace ManaPHP;

use ManaPHP\Di\Attribute\Inject;
use Psr\Container\ContainerInterface;

class Kernel extends Component
{
    #[Inject] protected ContainerInterface $container;
    #[Inject] protected AliasInterface $alias;
    #[Inject] protected EnvInterface $env;
    #[Inject] protected ConfigInterface $config;

    protected string $rootDir;

    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;

        $GLOBALS['Psr\Container\ContainerInterface'] = $this->container;
    }

    public function registerDefaultDependencies(): void
    {
        $this->container->set('Psr\SimpleCache\CacheInterface', 'ManaPHP\Caching\SimpleCache');
        $this->container->set('Psr\Log\LoggerInterface', 'ManaPHP\Logging\Psr3');
    }

    public function registerDefaultAliases(): void
    {
        $root = $this->rootDir;

        $this->alias->set('@public', "$root/public");
        $this->alias->set('@app', "$root/app");
        $this->alias->set('@views', "$root/app/Views");
        $this->alias->set('@root', $root);
        $this->alias->set('@runtime', "$root/runtime");
        $this->alias->set('@resources', "$root/resources");
        $this->alias->set('@config', "$root/config");
    }

    public function registerAppAliases(array $aliases): void
    {
        foreach ($aliases as $k => $v) {
            $this->alias->set($k, $v);
        }
    }

    public function registerAppFactories(array $factories): void
    {
        foreach ($factories as $interface => $definitions) {
            foreach ($definitions as $name => $definition) {
                if (is_string($definition) && $definition[0] === '#') {
                    $definition = "$interface$definition";
                }

                $this->container->set("$interface#$name", $definition);

                if ($name === 'default') {
                    $this->container->set($interface, "#$name");
                }
            }
        }
    }

    public function registerAppDependencies(array $dependencies): void
    {
        foreach ($dependencies as $id => $definition) {
            $this->container->set($id, $definition);
        }
    }

    public function bootBootstrappers(array $bootstrappers): void
    {
        foreach ($bootstrappers as $key => $value) {
            /** @var \ManaPHP\BootstrapperInterface $bootstrapper */
            if (is_int($key)) {
                $bootstrapper = $this->container->get($value);
            } else {
                $this->container->set($key, $value);
                $bootstrapper = $this->container->get($key);
            }

            $bootstrapper->bootstrap($this->container);
        }
    }

    public function detectCoroutineCanEnabled(): bool
    {
        return PHP_SAPI === 'cli' && extension_loaded('swoole');
    }

    public function start(string $server): void
    {
        if (!defined('MANAPHP_COROUTINE_ENABLED')) {
            define('MANAPHP_COROUTINE_ENABLED', $this->detectCoroutineCanEnabled());
        }

        $this->registerDefaultDependencies();
        $this->registerDefaultAliases();

        $this->env->load();

        $this->config->load();

        $this->registerAppAliases($this->config->get('aliases', []));
        $this->registerAppFactories($this->config->get('factories', []));
        $this->registerAppDependencies($this->config->get('dependencies', []));
        $this->bootBootstrappers($this->config->get('bootstrappers', []));

        /** @var string|ServerInterface $server */
        $server = $this->container->get($server);
        $server->start();
    }
}