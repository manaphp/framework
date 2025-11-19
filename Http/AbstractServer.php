<?php

declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\BootstrapperFactory;
use ManaPHP\Debugging\DebuggerInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\ListenerProviderInterface;
use ManaPHP\Http\Metrics\ExporterInterface;
use ManaPHP\Http\Router\MappingScannerInterface;
use ManaPHP\Http\Server\Listeners\LogServerStatusListener;
use ManaPHP\Http\Server\Listeners\RenameProcessTitleListener;
use ManaPHP\Swoole\ProcessesInterface;
use ManaPHP\Swoole\WorkersInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Throwable;
use function date;
use function ob_flush;
use function preg_replace;
use function sprintf;
use function strlen;

abstract class AbstractServer implements ServerInterface
{
    #[Autowired] protected BootstrapperFactory $bootstrapperFactory;
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
                $this->bootstrapperFactory->bootstrap($name);
            }
        }

        foreach ($this->listeners as $listener) {
            $this->listenerProvider->add($listener);
        }
    }

    public function write(string $chunk): bool
    {
        if ($chunk !== '') {
            echo sprintf('%X', strlen($chunk)) . "\r\n" . $chunk . "\r\n";
        } else {
            echo "0\r\n\r\n";
        }

        ob_flush();

        return true;
    }

    protected function formatException(Throwable $throwable): string
    {
        $str = date('c') . ' ' . $throwable::class . ': ' . $throwable->getMessage() . PHP_EOL;
        $str .= '    at ' . $throwable->getFile() . ':' . $throwable->getLine() . PHP_EOL;
        $str .= preg_replace('/#\d+\s/', '    at ', $throwable->getTraceAsString());

        return $str . PHP_EOL;
    }
}
