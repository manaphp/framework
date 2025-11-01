<?php

declare(strict_types=1);

namespace ManaPHP\Http\Middlewares;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Helper\SuppressWarnings;
use ManaPHP\Helper\Uuid;
use ManaPHP\Http\Event\RequestBegin;
use ManaPHP\Http\RequestInterface;

class RequestIdMiddleware
{
    #[Autowired] protected RequestInterface $request;

    public function onBegin(#[Event] RequestBegin $event): void
    {
        SuppressWarnings::unused($event);

        if ($this->request->header('x-request-id') === null) {
            $this->request->getContext()->headers['x-request-id'] = Uuid::v4();
        }
    }
}
