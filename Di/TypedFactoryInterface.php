<?php
declare(strict_types=1);

namespace ManaPHP\Di;

/**
 * @template T
 */
interface TypedFactoryInterface
{
    /**
     * @return T
     */
    public function get(string $name): object;
}