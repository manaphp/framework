<?php

declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Http\Request\FileInterface;

interface RequestInterface
{
    public function prepare(array $GET, array $POST, array $SERVER, ?string $RAW_BODY, array $COOKIE, array $FILES
    ): void;

    public function getContext(int $cid = 0): RequestContext;

    public function rawBody(): string;

    public function all(): array;

    public function validate(array $constraints): array;

    public function only(array $names): array;

    public function except(array $names): array;

    public function input(string $name, mixed $default = null): mixed;

    public function query(string $name, mixed $default = null): string;

    public function header(string $name, ?string $default = null): ?string;

    public function headers(): array;

    public function set(string $name, mixed $value): static;

    public function delete(string $name): static;

    public function server(string $name, mixed $default = null): mixed;

    public function method(): string;

    public function scheme(): string;

    public function isAjax(): bool;

    public function ip(): string;

    /**
     * Gets attached files as \ManaPHP\Http\Request\FileInterface compatible instances
     *
     * @param bool $onlySuccessful
     *
     * @return FileInterface[]
     */
    public function files(bool $onlySuccessful = true): array;

    public function file(?string $key = null): ?FileInterface;

    public function origin(bool $strict = true): string;

    public function url(): string;

    public function elapsed(int $precision = 3): float;

    public function path(): string;

    public function attribute(string $name): mixed;

    public function attributes(): array;

    public function setAttribute(string $name, mixed $value): void;

    public function removeAttribute(string $name): void;

    public function setHandler(string $handler): void;

    public function handler(): ?string;
}
