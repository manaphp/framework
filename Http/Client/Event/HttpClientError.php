<?php

declare(strict_types=1);

namespace ManaPHP\Http\Client\Event;

use ManaPHP\Eventing\Attribute\TraceLevel;
use ManaPHP\Http\Client\Request;
use ManaPHP\Http\Client\Response;
use ManaPHP\Http\ClientInterface;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::NOTICE)]
class HttpClientError
{
    public function __construct(
        public ClientInterface $client,
        public string $method,
        public string|array $url,
        public Request $request,
        public ?Response $response,
    ) {

    }
}
