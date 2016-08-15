<?php
namespace ManaPHP\Serializer\Adapter;

use ManaPHP\Serializer\AdapterInterface;

class JsonPhp implements AdapterInterface
{
    /**
     * @param mixed $data
     *
     * @return bool
     */
    public function _isCanJsonSafely($data)
    {
        if (is_scalar($data) || $data === null) {
            return true;
        } elseif (is_array($data)) {
            foreach ($data as $v) {
                if (!$this->_isCanJsonSafely($v)) {
                    return false;
                }
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * @param mixed $data
     *
     * @return string
     * @throws \ManaPHP\Serializer\Adapter\Exception
     */
    public function serialize($data)
    {
        if (is_scalar($data) || $data === null) {
            $wrappedData = ['__wrapper__' => $data];
            $serialized = json_encode($wrappedData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } elseif ($this->_isCanJsonSafely($data)) {
            $serialized = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($serialized === false) {
                throw new Exception('json_encode failed: ' . json_last_error_msg());
            }
        } else {
            $serialized = serialize($data);
        }

        return $serialized;
    }

    /**
     * @param string $serialized
     *
     * @return mixed
     * @throws \ManaPHP\Serializer\Adapter\Exception
     */
    public function deserialize($serialized)
    {
        if ($serialized[0] === '{' || $serialized[0] === '[') {
            $data = json_decode($serialized, true);
            if ($data === null) {
                throw new Exception('json_encode failed: ' . json_last_error_msg());
            }
            if (!is_array($data)) {
                throw new Exception('json serialized data has been corrupted.');
            }

            if (isset($data['__wrapper__']) && count($data) === 1) {
                return $data['__wrapper__'];
            } else {
                return $data;
            }
        } else {
            $data = unserialize($serialized);
            if ($data === false) {
                throw new Exception('unserialize failed: ' . error_get_last()['message']);
            } else {
                return $data;
            }
        }
    }
}