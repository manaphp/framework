<?php

declare(strict_types=1);

namespace ManaPHP\Ws\Router;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Http\RouterInterface;
use ManaPHP\Ws\Router\Attribute\WebSocketMapping;
use ReflectionClass;
use function str_contains;
use function str_ends_with;

class MappingScanner implements MappingScannerInterface
{
    #[Autowired] protected RouterInterface $router;

    #[Autowired] protected array $files
        = ['@app/Controllers/*Controller.php',
        ];

    protected function load(): void
    {
        foreach ($this->files as $file) {
            if (str_contains($file, '*')) {
                foreach (LocalFS::glob($file) as $path) {
                    require_once $path;
                }
            } else {
                require_once $file;
            }
        }
    }

    public function scan(): void
    {
        $this->load();

        foreach (get_declared_classes() as $class) {
            if (!str_ends_with($class, 'Controller')) {
                continue;
            }

            $rClass = new ReflectionClass($class);

            $webSocketMappings = $rClass->getAttributes(WebSocketMapping::class);
            if ($webSocketMappings !== []) {
                $webSocketMapping = $webSocketMappings[0]->newInstance();
                $this->router->addGet($webSocketMapping->getPath(), $class);
            }
        }
    }

    public function bootstrap(): void
    {
        $this->scan();
    }
}
