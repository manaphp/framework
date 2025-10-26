<?php
declare(strict_types=1);

namespace ManaPHP\Di;

/**
 * @template T
 */
interface FactoriedFactoryInterface
{
    /**
     * @return class-string<T>
     */
    public function getType(): string;

    /**
     * @param string $name
     * @return T
     */
    public function get(string $name): mixed;

    /**
     * @return array<string,T>
     */
    public function getDefinitions(): array;

    /**
     * @return array<string>
     */
    public function getNames(): array;
}