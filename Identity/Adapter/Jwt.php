<?php

namespace ManaPHP\Identity\Adapter;

use ManaPHP\Exception\ExpiredCredentialException;
use ManaPHP\Exception\InvalidCredentialException;
use ManaPHP\Exception\NoCredentialException;
use ManaPHP\Exception\NotBeforeCredentialException;
use ManaPHP\Identity;

/**
 * Class Jwt
 * @package ManaPHP\Identity\Adapter
 * @property \ManaPHP\Http\RequestInterface $request
 */
class Jwt extends Identity
{
    /**
     * @var string
     */
    protected $_alg;
    /**
     * @var array
     */
    protected $_key = [];

    /**
     * @var int
     */
    protected $_ttl = 86400;

    /**
     * Jwt constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->_alg = isset($options['alg']) ? $options['alg'] : 'HS256';
        $this->_key = isset($options['key']) ? (array)$options['key'] : [$this->crypt->getDerivedKey('jwt')];

        if (isset($options['ttl'])) {
            $this->_ttl = $options['ttl'];
        }
    }

    /**
     * @param array $claims
     *
     * @return string
     */
    public function encode($claims)
    {
        if (!isset($claims['exp'])) {
            $claims['exp'] = time() + $this->_ttl;
        }

        $header = $this->base64urlEncode(json_encode(['alg' => $this->_alg, 'typ' => 'JWT']));
        $payload = $this->base64urlEncode(json_encode($claims, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $signature = $this->base64urlEncode(hash_hmac(strtr($this->_alg, ['HS' => 'sha']), "$header.$payload", $this->_key[0], true));

        return "$header.$payload.$signature";
    }

    /**
     * @param string $token
     *
     * @return array
     */
    public function decode($token)
    {
        $parts = explode('.', $token, 5);
        if (count($parts) !== 3) {
            throw new InvalidCredentialException(['The JWT `:token` must have one dot', 'token' => $token]);
        }

        list($header, $payload, $signature) = $parts;
        $decoded_header = json_decode($this->base64urlDecode($header), true);
        if (!$decoded_header) {
            throw new InvalidCredentialException(['The JWT header `:header` is not distinguished', 'header' => $header]);
        }

        if (!isset($decoded_header['alg'])) {
            throw new InvalidCredentialException(['The JWT alg field is missing: `:token`', 'token' => $token]);
        }

        if ($decoded_header['alg'] !== $this->_alg) {
            throw new InvalidCredentialException(['The JWT alg `:alg` is not same as configured :alg2', 'alg' => $decoded_header['alg'], 'alg2' => $this->_alg]);
        }

        if (!$decoded_header['typ']) {
            throw new InvalidCredentialException(['The JWT typ field is missing: `:token`', 'token' => $token]);
        }

        if ($decoded_header['typ'] !== 'JWT') {
            throw new InvalidCredentialException(['The JWT typ `:typ` is not JWT', 'typ' => $decoded_header['typ']]);
        }

        $success = false;
        /** @noinspection ForeachSourceInspection */
        foreach ($this->_key as $key) {
            if ($this->base64urlEncode(hash_hmac(strtr($this->_alg, ['HS' => 'sha']), "$header.$payload", $key, true)) === $signature) {
                $success = true;
                break;
            }
        }

        if (!$success) {
            throw new InvalidCredentialException(['signature is not corrected: :signature', 'signature' => $signature]);
        }

        $claims = json_decode($this->base64urlDecode($payload), true);
        if (!is_array($claims)) {
            throw new InvalidCredentialException('payload is not array.');
        }

        if (isset($claims['exp']) && time() > $claims['exp']) {
            throw new ExpiredCredentialException('token is expired.');
        }

        if (isset($claims['nbf']) && time() < $claims['nbf']) {
            throw new NotBeforeCredentialException('token is not active.');
        }

        return $claims;
    }

    /**
     * @param bool $silent
     *
     * @return static
     */
    public function authenticate($silent = true)
    {
        $token = $this->request->getAccessToken();
        if (!$token && !$silent) {
            throw new NoCredentialException('no token');
        }
        $claims = $this->decode($token);
        return $this->setClaims($claims);
    }
}