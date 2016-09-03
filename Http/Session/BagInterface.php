<?php

namespace ManaPHP\Http\Session;

/**
 * ManaPHP\Http\Session\BagInterface initializer
 */
interface BagInterface
{
    /**
     * Destroy the session bag
     */
    public function destroy();

    /**
     * Setter of values
     *
     * @param string $property
     * @param mixed  $value
     */
    public function set($property, $value);

    /**
     * Getter of values
     *
     * @param string $property
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    public function get($property = null, $defaultValue = null);

    /**
     * Isset property
     *
     * @param string $property
     *
     * @return bool
     */
    public function has($property);

    /**
     * Unset property
     *
     * @param string $property
     */
    public function remove($property);
}