<?php

namespace ManaPHP\Store\Adapter;

use ManaPHP\Store;

/**
 * Class Redis
 *
 * @package ManaPHP\Store\Adapter
 *
 * @property \Redis $redis
 */
class Redis extends Store
{
    /**
     * @var string
     */
    protected $key = 'manaphp:store';

    /**
     * Redis constructor.
     *
     * @param string|array|\ConfManaPHP\Store\Adapter\Redis $options
     */
    public function __construct($options = [])
    {
        if (is_object($options)) {
            $options = (array)$options;
        }

        if (is_string($options)) {
            $options = ['key' => $options];
        }

        if (isset($options['key'])) {
            $this->key .= $options['key'];
        }
    }

    /**
     * Fetch content
     *
     * @param string $id
     *
     * @return string|false
     * @throws \ManaPHP\Store\Adapter\Exception
     */
    public function _get($id)
    {
        return $this->redis->hGet($this->key, $id);
    }

    /**
     * @param array $ids
     *
     * @return array
     */
    public function _mGet($ids)
    {
        return $this->redis->hMGet($this->key, $ids);
    }

    /**
     * Caches content
     *
     * @param string $id
     * @param string $value
     *
     * @return void
     * @throws \ManaPHP\Store\Adapter\Exception
     */
    public function _set($id, $value)
    {
        $this->redis->hSet($this->key, $id, $value);
    }

    /**
     * @param array $idValues
     *
     * @return void
     */
    public function _mSet($idValues)
    {
        $this->redis->hMset($this->key, $idValues);
    }

    /**
     * Delete content
     *
     * @param string $id
     *
     * @void
     * @throws \ManaPHP\Store\Adapter\Exception
     */
    public function _delete($id)
    {
        $this->redis->hDel($this->key, $id);
    }

    /**
     * Check if id exists
     *
     * @param string $id
     *
     * @return bool
     * @throws \ManaPHP\Store\Adapter\Exception
     */
    public function _exists($id)
    {
        return $this->redis->hExists($this->key, $id);
    }
}