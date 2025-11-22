<?php

declare(strict_types=1);

namespace ManaPHP\Http\Event;

use JsonSerializable;
use ManaPHP\Eventing\Attribute\TraceLevel;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\ResponseInterface;
use Psr\Log\LogLevel;
use function strlen;

#[TraceLevel(LogLevel::NOTICE)]
class RequestEnd implements JsonSerializable
{
    public function __construct(
        public RequestInterface  $request,
        public ResponseInterface $response,
    )
    {

    }

    public function jsonSerialize(): array
    {
        return [
            'uri'            => $this->request->path(),
            'http_code'      => $this->response->getStatusCode(),
            'content-type'   => $this->response->getContentType(),
            'content-length' => strlen($this->response->getContent() ?? ''),
        ];
    }
}
