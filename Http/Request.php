<?php

declare(strict_types=1);

namespace ManaPHP\Http;

use JsonSerializable;
use ManaPHP\Coroutine\ContextAware;
use ManaPHP\Coroutine\ContextManagerInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Lazy;
use ManaPHP\Di\MakerInterface;
use ManaPHP\Http\Request\File;
use ManaPHP\Http\Request\FileInterface;
use ManaPHP\Http\Request\Proxy;
use ManaPHP\Validating\ValidatorInterface;
use function array_merge;
use function count;
use function current;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function json_parse;
use function microtime;
use function parse_str;
use function round;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function strpos;
use function strtolower;
use function substr;

class Request implements RequestInterface, JsonSerializable, ContextAware
{
    #[Autowired] protected ContextManagerInterface $contextManager;
    #[Autowired] protected MakerInterface|Lazy $maker;
    #[Autowired] protected ValidatorInterface|Lazy $validator;

    #[Autowired] protected bool $proxy = false;

    public function __construct()
    {
        if ($this->proxy) {
            $_GET = new Proxy($this, '_GET');
            $_POST = new Proxy($this, '_POST');
            $_REQUEST = new Proxy($this, '_REQUEST');
            $_FILES = new Proxy($this, '_FILES');
            $_COOKIE = new Proxy($this, '_COOKIE');
            $_SERVER = new Proxy($this, '_SERVER');
        }
    }

    public function getContext(): RequestContext
    {
        return $this->contextManager->getContext($this);
    }

    public function prepare(
        array $GET,
        array $POST,
        array $SERVER,
        ?string $RAW_BODY = null,
        array $COOKIE = [],
        array $FILES = []
    ): void {
        $context = $this->getContext();

        if (!$POST
            && (isset($SERVER['REQUEST_METHOD']) && !in_array($SERVER['REQUEST_METHOD'], ['GET', 'OPTIONS'], true))
        ) {
            if (isset($SERVER['CONTENT_TYPE'])
                && str_contains($SERVER['CONTENT_TYPE'], 'application/json')
            ) {
                $POST = json_parse($RAW_BODY);
            } else {
                parse_str($RAW_BODY, $POST);
            }

            if (!is_array($POST)) {
                $POST = [];
            }
        }

        $context->_GET = $GET;
        $context->_POST = $POST;
        $context->_REQUEST = $POST === [] ? $GET : array_merge($GET, $POST);
        $context->_SERVER = $SERVER;
        $context->rawBody = $RAW_BODY;
        $context->_COOKIE = $COOKIE;
        $context->_FILES = $FILES;

        $headers = [];
        foreach ($SERVER as $key => $val) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = $val;
            }
        }

        if (isset($SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = $SERVER['CONTENT_TYPE'];
        }

        if (isset($SERVER['CONTENT_LENGTH'])) {
            $headers['content-length'] = $SERVER['CONTENT_LENGTH'];
        }

        $context->headers = $headers;
    }

    public function rawBody(): string
    {
        return $this->getContext()->rawBody;
    }

    public function all(): array
    {
        return $this->getContext()->_REQUEST;
    }

    public function validate(array $constraints): array
    {
        return $this->validator->validateValues($this->all(), $constraints);
    }

    public function only(array $names): array
    {
        $data = [];

        foreach ($this->all() as $name => $val) {
            if (in_array($name, $names, true)) {
                $data[$name] = $val;
            }
        }

        return $data;
    }

    public function except(array $names): array
    {
        $data = [];

        foreach ($this->all() as $name => $val) {
            if (!in_array($name, $names, true)) {
                $data[$name] = $val;
            }
        }

        return $data;
    }

    public function input(string $name, mixed $default = null): mixed
    {
        return $this->all()[$name] ?? $default;
    }

    public function query(string $name, mixed $default = null): string
    {
        return $this->getContext()->_GET[$name] ?? $default;
    }

    public function set(string $name, mixed $value): static
    {
        $context = $this->getContext();

        $context->_GET[$name] = $value;
        $context->_REQUEST[$name] = $value;

        return $this;
    }

    public function delete(string $name): static
    {
        $context = $this->getContext();

        unset($context->_GET[$name], $context->_POST[$name], $context->_REQUEST[$name]);

        return $this;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers()[strtolower($name)] ?? $default;
    }

    public function headers(): array
    {
        return $this->getContext()->headers;
    }

    public function server(?string $name = null, mixed $default = null): mixed
    {
        return $this->getContext()->_SERVER[$name] ?? $default;
    }

    public function method(): string
    {
        return $this->server('REQUEST_METHOD');
    }

    public function scheme(): string
    {
        if ($scheme = $this->server('REQUEST_SCHEME')) {
            return $scheme;
        } else {
            return $this->server('HTTPS') === 'on' ? 'https' : 'http';
        }
    }

    public function isAjax(): bool
    {
        return $this->header('x-requested-with') === 'XMLHttpRequest';
    }

    public function ip(): string
    {
        return $this->header('x-real-ip') ?: $this->server('REMOTE_ADDR');
    }

    public function files(bool $onlySuccessful = true): array
    {
        $r = [];

        foreach ($this->getContext()->_FILES as $key => $files) {
            if (isset($files[0])) {
                foreach ($files as $file) {
                    if (!$onlySuccessful || $file['error'] === UPLOAD_ERR_OK) {
                        $file['key'] = $key;

                        $r[] = $this->maker->make(File::class, [$file]);
                    }
                }
            } elseif (is_int($files['error'])) {
                $file = $files;
                if (!$onlySuccessful || $file['error'] === UPLOAD_ERR_OK) {
                    $file['key'] = $key;

                    $r[] = $this->maker->make(File::class, [$file]);
                }
            } else {
                $countFiles = count($files['error']);
                for ($i = 0; $i < $countFiles; $i++) {
                    if (!$onlySuccessful || $files['error'][$i] === UPLOAD_ERR_OK) {
                        $file = [
                            'key'      => $key,
                            'name'     => $files['name'][$i],
                            'type'     => $files['type'][$i],
                            'tmp_name' => $files['tmp_name'][$i],
                            'error'    => $files['error'][$i],
                            'size'     => $files['size'][$i],
                        ];
                        $r[] = $this->maker->make(File::class, [$file]);
                    }
                }
            }
        }

        return $r;
    }

    public function file(?string $key = null): ?FileInterface
    {
        $files = $this->files();

        if ($key === null) {
            return $files ? current($files) : null;
        } else {
            foreach ($files as $file) {
                if ($file->getKey() === $key) {
                    return $file;
                }
            }
            return null;
        }
    }

    public function origin(bool $strict = true): string
    {
        if ($origin = $this->header('origin')) {
            return $origin;
        }

        if (!$strict && ($referer = $this->header('referer'))) {
            if ($pos = strpos($referer, '?')) {
                $referer = substr($referer, 0, $pos);
            }

            if ($pos = strpos($referer, '://')) {
                $pos = strpos($referer, '/', $pos + 3);
                return $pos ? substr($referer, 0, $pos) : $referer;
            }
        }

        return '';
    }

    public function url(): string
    {
        return $this->scheme() . '://' . $this->header('host') . $this->path();
    }

    public function jsonSerialize(): array
    {
        return (array)$this->getContext();
    }

    public function elapsed(int $precision = 3): float
    {
        return round(microtime(true) - $this->server('REQUEST_TIME_FLOAT'), $precision);
    }

    public function path(): string
    {
        return $this->server('REQUEST_URI');
    }

    public function attribute(string $name): mixed
    {
        return $this->getContext()->attributes[$name] ?? null;
    }

    public function attributes(): array
    {
        return $this->getContext()->attributes;
    }

    public function setAttribute(string $name, mixed $value): void
    {
        $this->getContext()->attributes[$name] = $value;
    }

    public function removeAttribute(string $name): void
    {
        unset($this->getContext()->attributes[$name]);
    }

    public function setHandler(string $handler): void
    {
        $this->getContext()->handler = $handler;
    }

    public function handler(): ?string
    {
        return $this->getContext()->handler;
    }

    public function setVariables(array $variables): void
    {
        $context = $this->getContext();
        $context->variables = $variables;

        foreach ($variables as $k => $v) {
            if (is_string($k)) {
                $context->_REQUEST[$k] = $v;
            }
        }
    }
}