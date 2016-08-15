<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/19
 * Time: 15:52
 */
namespace ManaPHP\Http;

use ManaPHP\Http\Session\Exception;
use ManaPHP\Utility\Text;

/**
 * ManaPHP\Http\Session\AdapterInterface initializer
 */
class Session implements SessionInterface, \ArrayAccess
{
    public function __construct()
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        session_start();

        $message = error_get_last()['message'];
        if (Text::startsWith($message, 'session_start():')) {
            throw new Exception($message);
        }
    }

    public function __destruct()
    {
        session_write_close();
    }

    /**
     * Gets a session variable from an application context
     *
     * @param string $name
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    public function get($name, $defaultValue = null)
    {
        if (isset($_SESSION[$name])) {
            return $_SESSION[$name];
        } else {
            return $defaultValue;
        }
    }

    /**
     * Sets a session variable in an application context
     *
     * @param string $name
     * @param mixed  $value
     */
    public function set($name, $value)
    {
        $_SESSION[$name] = $value;
    }

    /**
     * Check whether a session variable is set in an application context
     *
     * @param string $name
     *
     * @return boolean
     */
    public function has($name)
    {
        return isset($_SESSION[$name]);
    }

    /**
     * Removes a session variable from an application context
     *
     * @param string $name
     */
    public function remove($name)
    {
        unset($_SESSION[$name]);
    }

    /**
     * Destroys the active session
     *
     * @return void
     * @throws \ManaPHP\Http\Session\Exception
     */
    public function destroy()
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        if (!session_destroy()) {
            throw new Exception(error_get_last()['message']);
        }
    }

    /**
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * @param mixed $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        if (is_array($_SESSION)) {
            $data = $_SESSION;
        } else {
            $data = [];
        }

        return $data;
    }
}