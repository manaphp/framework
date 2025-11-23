<?php

declare(strict_types=1);

namespace ManaPHP\Token;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Security\CryptInterface;

class ScopedJwt implements ScopedJwtInterface
{
    #[Autowired] protected JwtInterface $jwt;
    #[Autowired] protected CryptInterface $crypt;

    #[Autowired] protected array $keys = [];

    public function getKey(string $scope): string
    {
        if (($key = $this->keys[$scope] ?? null) === null) {
            $key = $this->keys[$scope] = $this->crypt->getDerivedKey("jwt:$scope");
        }

        return $key;
    }

    public function encode(array $claims, int $ttl, string $scope): string
    {
        if (isset($claims['scope'])) {
            throw new MisuseException('Scope field already exists in JWT claims.', ['existing_scope' => $claims['scope'], 'new_scope' => $scope]);
        }

        $claims['scope'] = $scope;

        return $this->jwt->encode($claims, $ttl, $this->getKey($scope));
    }

    public function decode(string $token, string $scope, bool $verify = true): array
    {
        $claims = $this->jwt->decode($token, false);

        if (!isset($claims['scope'])) {
            throw new ScopeException('Scope field does not exist in JWT claims.', ['token_preview' => substr($token, 0, 50), 'expected_scope' => $scope]);
        }

        if ($claims['scope'] !== $scope) {
            throw new ScopeException('The scope "{scope1}" does not match the expected scope "{scope2}".', ['scope1' => $claims['scope'], 'scope2' => $scope]);
        }

        if ($verify) {
            $this->verify($token, $scope);
        }

        return $claims;
    }

    public function verify(string $token, string $scope): void
    {
        $this->jwt->verify($token, $this->getKey($scope));
    }
}
