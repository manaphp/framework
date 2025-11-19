<?php
declare(strict_types=1);

namespace ManaPHP;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\ContainerInterface;

class BootstrapperFactory
{
    #[Autowired] protected ContainerInterface $container;

    public function bootstrap($name): void
    {
        $bootstrapper = $this->container->get($name);
        $bootstrapper->bootstrap();
    }
}