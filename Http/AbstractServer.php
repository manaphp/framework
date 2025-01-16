<?php

declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\BootstrapperInterface;
use ManaPHP\Debugging\DebuggerInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\ListenerProviderInterface;
use ManaPHP\Http\Metrics\ExporterInterface;
use ManaPHP\Http\Router\MappingScannerInterface;
use ManaPHP\Http\Server\Listeners\LogServerStatusListener;
use ManaPHP\Http\Server\Listeners\RenameProcessTitleListener;
use ManaPHP\Swoole\ProcessesInterface;
use ManaPHP\Swoole\WorkersInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use function ob_flush;
use function sprintf;

abstract class AbstractServer implements ServerInterface
{
    #[Autowired] protected ContainerInterface $container;
    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected ListenerProviderInterface $listenerProvider;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected RouterInterface $router;
    #[Autowired] protected RequestHandlerInterface $requestHandler;

    #[Autowired] protected string $host = '0.0.0.0';
    #[Autowired] protected int $port = 9501;
    #[Autowired] protected array $listeners
        = [
            LogServerStatusListener::class,
            RenameProcessTitleListener::class
        ];
    #[Autowired] protected array $bootstrappers
        = [
            DebuggerInterface::class,
            WorkersInterface::class,
            ExporterInterface::class,
            ProcessesInterface::class,
            MappingScannerInterface::class,
        ];

    protected function bootstrap(): void
    {
        foreach ($this->bootstrappers as $name) {
            if ($name !== '' && $name !== null) {
                /** @var BootstrapperInterface $bootstrapper */
                $bootstrapper = $this->container->get($name);
                $bootstrapper->bootstrap();
            }
        }

        foreach ($this->listeners as $listener) {
            $this->listenerProvider->add($listener);
        }
    }

    public function write(?string $chunk): void
    {
        if ($chunk !== null) {
            echo sprintf('%X', strlen($chunk)) . "\r\n" . $chunk . "\r\n";
        } else {
            echo "0\r\n\r\n";
        }

        ob_flush();
    }
}
