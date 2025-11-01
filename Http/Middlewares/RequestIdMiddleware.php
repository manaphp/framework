<?php

declare(strict_types=1);

namespace ManaPHP\Http\Middlewares;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Helper\SuppressWarnings;
use ManaPHP\Http\Event\RequestBegin;
use ManaPHP\Http\RequestInterface;
use function bin2hex;
use function random_bytes;

class RequestIdMiddleware
{
    #[Autowired] protected RequestInterface $request;

    public function onBegin(#[Event] RequestBegin $event): void
    {
        SuppressWarnings::unused($event);

        if ($this->request->header('x-request-id') === null) {
            $this->request->getContext()->headers['x-request-id'] = bin2hex(random_bytes(16));
        }
    }
}
