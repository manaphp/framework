<?php

declare(strict_types=1);

namespace ManaPHP\Cli;

use ManaPHP\Cli\Event\CliInvoked;
use ManaPHP\Cli\Event\CliInvoking;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\InvokerInterface;
use ManaPHP\Helper\Str;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionMethod;
use function array_shift;
use function basename;
use function class_exists;
use function count;
use function explode;
use function get_class_methods;
use function is_int;
use function lcfirst;
use function method_exists;
use function str_contains;
use function str_ends_with;
use function str_replace;

class Handler implements HandlerInterface
{
    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected ArgumentsResolverInterface $argumentsResolver;
    #[Autowired] protected ConsoleInterface $console;
    #[Autowired] protected ContainerInterface $container;
    #[Autowired] protected OptionsInterface $options;
    #[Autowired] protected InvokerInterface $invoker;

    #[Autowired] protected array $commands = ['App\Commands\*Command', 'ManaPHP\Commands\*Command'];

    protected string $entrypoint;
    protected ?string $command;
    protected ?string $action;
    protected array $params;

    protected function route(array $args): void
    {
        $this->entrypoint = array_shift($args);

        if ($args === [] || $args === ['--help'] || $args === ['-h']) {
            $this->command = 'help';
            $this->action = 'commands';
            $this->params = [];
        } else {
            $cmd = array_shift($args);
            if (str_contains($cmd, ':')) {
                list($command, $action) = explode(':', $cmd, 2);
            } elseif (str_contains($cmd, '/')) {
                list($command, $action) = explode('/', $cmd, 2);
            } else {
                $command = basename($cmd, 'Command');
                $action = null;
            }

            if ($args === ['--help'] || $args === ['-h']) {
                $args = ['--command', $command];

                if ($action !== null) {
                    $args[] = '--action';
                    $args[] = $action;
                }

                $command = 'help';
                $action = 'command';
            }

            $this->command = $command;
            $this->action = $action ?? 'default';
            $this->params = $args;
        }
    }

    protected function getCommandClassName(string $command): ?string
    {
        foreach ($this->commands as $name) {
            if (class_exists($class = str_replace('*', $command, $name))) {
                return $class;
            }
        }

        return null;
    }

    protected function getMethod(string $command, string $action): ?string
    {
        $method = $action . 'Action';
        if (method_exists($command, $method)) {
            return $method;
        }

        if ($action === 'default') {
            $methods = [];
            foreach (get_class_methods($command) as $method) {
                if (str_ends_with($method, 'Action')) {
                    $methods[] = $method;
                }
            }

            if (count($methods) === 1) {
                return $methods[0];
            }
        }

        return null;
    }

    public function handle(array $args): int
    {
        $this->route($args);

        $this->options->parse($this->params);

        $command = Str::pascalize($this->command);
        $action = Str::camelize($this->action);

        $cmd = lcfirst($command) . ':' . $action;

        if (($class = $this->getCommandClassName($command)) === null) {
            return $this->console->error("`$cmd` command is not exists");
        }

        $instance = $this->container->get($class);

        if (($method = $this->getMethod($class, $action)) === null) {
            return $this->console->error("`$cmd` action is not exists");
        }

        $this->eventDispatcher->dispatch(new CliInvoking($this, $instance, $method, $action));
        $arguments = $this->argumentsResolver->resolve(new ReflectionMethod($instance, $method));
        $return = $this->invoker->call([$instance, $method], $arguments);
        $this->eventDispatcher->dispatch(new CliInvoked($this, $instance, $method, $action, $return));
        if ($return === null) {
            return 0;
        } elseif (is_int($return)) {
            return $return;
        } else {
            return $this->console->error($return);
        }
    }

    public function getEntrypoint(): string
    {
        return $this->entrypoint;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getParams(): array
    {
        return $this->params;
    }
}
