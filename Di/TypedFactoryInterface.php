<?php
declare(strict_types=1);

namespace ManaPHP\Di;

/**
 * @template T
 */
interface TypedFactoryInterface
{
    /**
     * @return class-string<T>
     */
    public function getType(): string;

    /**
     * @param string $name
     * @return T
     */
    public function getInstance(string $name): mixed;

    /**
     * @return array<string,T>
     */
    public function getDefinitions(): array;

    /**
     * @return array<string>
     */
    public function getNames(): array;
}