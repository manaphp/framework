<?php

declare(strict_types=1);

namespace ManaPHP\Http\Client\Event;

use JsonSerializable;
use ManaPHP\Eventing\Attribute\TraceLevel;
use ManaPHP\Http\Client\Request;
use ManaPHP\Http\Client\Response;
use ManaPHP\Http\ClientInterface;
use Psr\Log\LogLevel;
use Stringable;
use function json_stringify;

#[TraceLevel(LogLevel::DEBUG)]
class HttpClientRequested implements JsonSerializable, Stringable
{
    public function __construct(
        public ClientInterface $client,
        public string          $method,
        public string|array    $url,
        public Request         $request,
        public Response        $response,
    )
    {

    }

    public function jsonSerialize(): array
    {
        return [
            'url'      => $this->url,
            'method'   => $this->method,
            'response' => $this->response->jsonSerialize(),
        ];
    }

    public function __toString(): string
    {
        return json_stringify($this->jsonSerialize());
    }
}
