<?php
namespace ManaPHP;

/**
 * Interface ManaPHP\ApplicationInterface
 *
 * @package application
 */
interface ApplicationInterface
{
    /**
     * @return void
     */
    public function registerServices();

    /**
     * @return void
     */
    public function main();

    /**
     * @param \Exception|\Throwable $exception
     */
    public function handleException($exception);
}