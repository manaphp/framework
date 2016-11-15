<?php
namespace ManaPHP\Serializer\Adapter;

use ManaPHP\Serializer\Adapter\Php\Exception as PhpException;
use ManaPHP\Serializer\AdapterInterface;

/**
 * Class ManaPHP\Serializer\Adapter\Php
 *
 * @package serializer\adapter
 */
class Php implements AdapterInterface
{
    /**
     * @param mixed $data
     *
     * @return string
     */
    public function serialize($data)
    {
        if (!is_array($data)) {
            $data = ['__wrapper__' => $data];
        }

        return serialize($data);
    }

    /**
     * @param string $serialized
     *
     * @return mixed
     * @throws \ManaPHP\Serializer\Adapter\Exception
     */
    public function deserialize($serialized)
    {
        $data = unserialize($serialized);
        if ($data === false) {
            throw new PhpException('unserialize failed: :last_error_message'/**m066507d6397244b1c*/);
        }

        if (!is_array($data)) {
            throw new PhpException('de serialized data is not a array maybe it has been corrupted'/**m06a7e8b3300369f79*/);
        }

        if (isset($data['__wrapper__']) && count($data) === 1) {
            return $data['__wrapper__'];
        } else {
            return $data;
        }
    }
}