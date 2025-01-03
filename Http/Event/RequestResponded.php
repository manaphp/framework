<?php

declare(strict_types=1);

namespace ManaPHP\Http\Event;

use ManaPHP\Eventing\Attribute\TraceLevel;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\ResponseInterface;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::DEBUG)]
class RequestResponded
{
    public function __construct(public RequestInterface $request, public ResponseInterface $response)
    {

    }
}
