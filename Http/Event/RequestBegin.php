<?php

declare(strict_types=1);

namespace ManaPHP\Http\Event;

use JsonSerializable;
use ManaPHP\Eventing\Attribute\TraceLevel;
use ManaPHP\Http\RequestInterface;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::INFO)]
class RequestBegin implements JsonSerializable
{
    public function __construct(public RequestInterface $request)
    {

    }

    public function jsonSerialize(): array
    {
        return [
            'method'    => $this->request->method(),
            'url'       => $this->request->url(),
            'query'     => $this->request->server('QUERY_STRING'),
            'client_ip' => $this->request->ip(),
        ];
    }
}
