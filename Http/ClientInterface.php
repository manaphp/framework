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
    const USER_AGENT_CHROME = 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36';
    const USER_AGENT_FIREFOX = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:138.0) Gecko/20100101 Firefox/138.0';
    const CONTENT_TYPE_FORM = 'application/x-www-form-urlencoded; charset=UTF-8';
    
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
