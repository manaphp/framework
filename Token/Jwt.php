<?php

declare(strict_types=1);

namespace ManaPHP\Token;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Security\CryptInterface;
use function base64_decode;
use function base64_encode;
use function count;
use function explode;
use function hash_hmac;
use function is_array;
use function json_decode;
use function json_stringify;
use function rtrim;
use function strrpos;
use function strtr;
use function substr;
use function time;

class Jwt implements JwtInterface
{
    #[Autowired] protected CryptInterface $crypt;

    #[Autowired] protected string $alg = 'HS256';
    #[Autowired] protected ?string $key;

    protected function base64UrlEncode(string $str): string
    {
        return strtr(rtrim(base64_encode($str), '='), '+/', '-_');
    }

    protected function base64UrlDecode(string $str): ?string
    {
        $v = base64_decode(strtr($str, '-_', '+/'));
        return $v === false ? null : $v;
    }

    public function encode(array $claims, int $ttl, ?string $key = null): string
    {
        $key = $key ?? $this->key ?? $this->crypt->getDerivedKey('jwt');

        $claims['iat'] = time();
        $claims['exp'] = time() + $ttl;

        $header = $this->base64UrlEncode(json_stringify(['alg' => $this->alg, 'typ' => 'JWT']));
        $payload = $this->base64UrlEncode(json_stringify($claims));
        $hmac = hash_hmac(strtr($this->alg, ['HS' => 'sha']), "$header.$payload", $key, true);
        $signature = $this->base64UrlEncode($hmac);

        return "$header.$payload.$signature";
    }

    public function decode(string $token, bool $verify = true, ?string $key = null): array
    {
        if ($token === '') {
            throw new NoCredentialException('No credentials provided.');
        }

        $parts = explode('.', $token, 5);
        if (count($parts) !== 3) {
            throw new MalformedException('JWT token must have three parts separated by dots.', ['parts_count' => count($parts)]);
        }

        list($header, $payload) = $parts;

        //DO NOT use json_parse, it maybe generates a lot of Exceptions
        /** @noinspection JsonEncodingApiUsageInspection */
        if (!is_array($claims = json_decode($this->base64UrlDecode($payload), true))) {
            $decodedPayload = $this->base64UrlDecode($payload);
            throw new MalformedException('JWT payload is not an array.', ['payload_type' => gettype($claims), 'payload_preview' => is_string($decodedPayload) ? substr($decodedPayload, 0, 100) : null]);
        }

        //DO NOT use json_parse, it maybe generates a lot of Exceptions
        /** @noinspection JsonEncodingApiUsageInspection */
        $decoded_header = json_decode($this->base64UrlDecode($header), true);
        if (!$decoded_header) {
            $decodedHeader = $this->base64UrlDecode($header);
            throw new MalformedException('JWT header cannot be decoded or is invalid.', ['header_preview' => is_string($decodedHeader) ? substr($decodedHeader, 0, 100) : null, 'json_error' => json_last_error_msg()]);
        }

        if (!isset($decoded_header['alg'])) {
            throw new MalformedException('The JWT "alg" field is missing in the header.', ['header_keys' => array_keys($decoded_header), 'token_preview' => substr($token, 0, 50)]);
        }

        if ($decoded_header['alg'] !== $this->alg) {
            $decoded_alg = $decoded_header['alg'];
            throw new MalformedException('JWT algorithm "{decoded_alg}" does not match the expected algorithm "{alg}".', ['decoded_alg' => $decoded_alg, 'alg' => $this->alg]);
        }

        if (!$decoded_header['typ']) {
            throw new MalformedException('The JWT "typ" field is missing in the header for token "{token}".', ['token' => $token]);
        }

        if ($decoded_header['typ'] !== 'JWT') {
            throw new MalformedException('JWT typ "{typ}" is not "JWT".', ['typ' => $decoded_header['typ']]);
        }

        if (isset($claims['exp']) && time() > $claims['exp']) {
            throw new ExpiredException('JWT token has expired.', ['expired_at' => date('Y-m-d H:i:s', $claims['exp']), 'current_time' => date('Y-m-d H:i:s')]);
        }

        if (isset($claims['nbf']) && time() < $claims['nbf']) {
            throw new NotBeforeException('JWT token is not yet active.', ['active_at' => date('Y-m-d H:i:s', $claims['nbf']), 'current_time' => date('Y-m-d H:i:s')]);
        }

        if ($verify) {
            $this->verify($token, $key);
        }

        return $claims;
    }

    public function verify(string $token, ?string $key = null): void
    {
        if (($pos = strrpos($token, '.')) === false) {
            throw new MalformedException('JWT token must have three parts separated by dots.', ['token_length' => strlen($token)]);
        }

        $key = $key ?? $this->key ?? $this->crypt->getDerivedKey('jwt');

        $data = substr($token, 0, $pos);
        $signature = substr($token, $pos + 1);
        $hmac = hash_hmac(strtr($this->alg, ['HS' => 'sha']), $data, $key, true);

        if ($this->base64UrlEncode($hmac) !== $signature) {
            throw new SignatureException('JWT signature verification failed.', ['algorithm' => $this->alg, 'token_preview' => substr($token, 0, 50), 'expected_signature' => substr($this->base64UrlEncode($hmac), 0, 20), 'received_signature' => substr($signature, 0, 20)]);
        }
    }
}
