<?php

declare(strict_types=1);

namespace ManaPHP\Http\Session\Adapter;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\AbstractSession;
use ManaPHP\Http\Session\Adapter\Cookie\Exception as CookieException;
use ManaPHP\Security\CryptInterface;
use function count;
use function explode;
use function gettype;
use function is_array;
use function json_parse;
use function json_stringify;
use function md5;
use function session_get_cookie_params;
use function time;

class Cookie extends AbstractSession
{
    #[Autowired] protected CryptInterface $crypt;

    #[Autowired] protected ?string $key;
    #[Autowired] protected string $salt = 'cookie.session';

    public function do_read(string $session_id): string
    {
        $data = $this->cookies->get($session_id) ?: '';
        if ($data === '') {
            return '';
        }

        $parts = explode('.', $data, 2);

        if (count($parts) !== 2) {
            throw new CookieException('Cookie format invalid: {data}.', ['data' => $data]);
        }

        $key = $this->key ?? $this->crypt->getDerivedKey($this->salt);

        if (md5($parts[0] . $key) !== $parts[1]) {
            throw new CookieException('Cookie hash invalid: {data}.', ['data' => $data]);
        }

        $payload = json_parse($parts[0]);
        if (!is_array($payload)) {
            throw new CookieException('Cookie payload is not an array, got "{type}".', ['type' => gettype($payload)]);
        }

        if (time() > $payload['exp']) {
            return '';
        }

        return $payload['data'];
    }

    public function do_write(string $session_id, string $data, int $ttl): bool
    {
        $params = session_get_cookie_params();

        $payload = base64_encode(json_stringify(['exp' => time() + $ttl, 'data' => $data]));
        $key = $this->key ?? $this->crypt->getDerivedKey($this->salt);
        $this->cookies->set(
            $session_id,
            $payload . '.' . md5($payload . $key),
            $params['lifetime'],
            $params['path'],
            $params['domain'],
            $params['secure']
        );

        return true;
    }

    public function do_touch(string $session_id, int $ttl): bool
    {
        return false;
    }

    public function do_destroy(string $session_id): void
    {
        $this->cookies->delete($session_id);
    }

    public function do_gc(int $ttl): void
    {
    }
}
