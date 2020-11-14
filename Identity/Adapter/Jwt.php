<?php

namespace ManaPHP\Identity\Adapter;

use ManaPHP\Identity;

/**
 * Class Jwt
 *
 * @package ManaPHP\Identity\Adapter
 * @property-read \ManaPHP\Http\RequestInterface $request
 */
class Jwt extends Identity
{
    /**
     * @var string
     */
    protected $_scope;

    /**
     * Jwt constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->_scope = $options['scope'] ?? $this->configure->id;
    }

    /**
     * @return static
     */
    public function authenticate()
    {
        if ($token = $this->request->getToken()) {
            $claims = $this->jwt->scopedDecode($token, $this->_scope);
            $this->setClaims($claims);
        }

        return $this;
    }
}