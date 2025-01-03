<?php

declare(strict_types=1);

namespace ManaPHP\Http\Event;

use ManaPHP\Eventing\Attribute\TraceLevel;
use ManaPHP\Http\ResponseInterface;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::DEBUG)]
class ResponseStringify
{
    public function __construct(public ResponseInterface $response)
    {

    }
}
