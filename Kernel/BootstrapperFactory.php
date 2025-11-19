<?php
declare(strict_types=1);

namespace ManaPHP\Kernel;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\ContainerInterface;
use ManaPHP\Kernel\BootstrapperFactory\Event\BootstrapperBootstrapping;
use Psr\EventDispatcher\EventDispatcherInterface;

class BootstrapperFactory
{
    #[Autowired] protected ContainerInterface $container;
    #[Autowired] protected EventDispatcherInterface $eventDispatcher;

    public function bootstrap($name): void
    {
        $this->eventDispatcher->dispatch(new BootstrapperBootstrapping($name));

        $bootstrapper = $this->container->get($name);
        $bootstrapper->bootstrap();
    }
}