<?php
declare(strict_types=1);

namespace ManaPHP\Di;

/**
 * @template T
 * @extends TypedFactoryInterface<T>
 */
interface FactoriedFactoryInterface extends TypedFactoryInterface
{
    /**
     * @return class-string<T>
     */
    public function getType(): string;

    /**
     * @return array<string,T>
     */
    public function getDefinitions(): array;

    /**
     * @return array<string>
     */
    public function getNames(): array;
}