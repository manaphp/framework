<?php

declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Http\Client\Response;

interface ClientInterface
{
    const HEADER_USER_AGENT = 'User-Agent';
    const HEADER_CONTENT_TYPE = 'Content-Type';
    const HEADER_CONTENT_LENGTH = 'Content-Length';
    const HEADER_ACCEPT = 'Accept';
    const HEADER_ACCEPT_ENCODING = 'Accept-Encoding';
    const HEADER_ACCEPT_CHARSET = 'Accept-Charset';
    const HEADER_X_REQUESTED_WITH = 'X-Requested-With';
    const HEADER_X_REQUEST_ID = 'X-Request-Id';
    const HEADER_AUTHORIZATION = 'Authorization';
    const HEADER_COOKIE = 'Cookie';
    const HEADER_HOST = 'Host';
    const HEADER_REFERER = 'Referer';
    const HEADER_ORIGIN = 'Origin';
    const HEADER_CACHE_CONTROL = 'Cache-Control';

    public function rest(
        string $method,
        string|array $url,
        string|array $body = [],
        array $headers = [],
        mixed $options = []
    ): Response;

    public function request(
        string $method,
        string|array $url,
        null|string|array $body = null,
        array $headers = [],
        array $options = []
    ): Response;

    public function get(string|array $url, array $headers = [], mixed $options = []): Response;

    public function post(
        string|array $url,
        string|array $body = [],
        array $headers = [],
        mixed $options = []
    ): Response;

    public function delete(string|array $url, array $headers = [], mixed $options = []): Response;

    public function put(string|array $url, string|array $body = [], array $headers = [], mixed $options = []): Response;

    public function patch(
        string|array $url,
        string|array $body = [],
        array $headers = [],
        mixed $options = []
    ): Response;

    public function head(
        string|array $url,
        string|array $body = [],
        array $headers = [],
        mixed $options = []
    ): Response;
}
