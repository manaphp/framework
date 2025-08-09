<?php

declare(strict_types=1);

namespace ManaPHP\Http\Response\Appenders;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\Response\AppenderInterface;
use ManaPHP\Http\ResponseInterface;

class RouteAppender implements AppenderInterface
{
    #[Autowired] protected ?bool $enabled;

    #[Config] protected string $app_env;

    public function append(RequestInterface $request, ResponseInterface $response): void
    {
        if (($this->enabled ?? $this->app_env === 'dev') && ($handler = $request->handler()) !== null) {
            $response->setHeader('X-Router-Route', $handler);
        }
    }
}
