<?php

namespace ManaPHP\Rpc\Amqp;

use ManaPHP\Component;
use ManaPHP\Rpc\Amqp\Engine\Php;

/**
 * @property-read \ManaPHP\Pool\ManagerInterface $poolManager
 */
class Client extends Component
{
    /**
     * @var string
     */
    protected $uri;

    /** @var EngineInterface */
    protected $engine;

    /**
     * @param string $uri
     */
    public function __construct($uri)
    {
        $this->uri = $uri;

        $this->engine = new Php($uri);
    }

    /**
     * @param string       $exchange
     * @param string       $routing_key
     * @param string|array $body
     * @param array        $properties
     * @param array        $options
     *
     * @return mixed
     */
    public function call($exchange, $routing_key, $body, $properties = [], $options = [])
    {
        $engine = $this->engine;
        if (is_array($body)) {
            $body = json_stringify($body);
            if (!isset($properties['content_type'])) {
                $properties['content_type'] = 'application/json';
            }
        }

        return $engine->call($exchange, $routing_key, $body, $properties, $options);
    }
}