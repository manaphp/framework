<?php

namespace ManaPHP\Http\Request;

/**
 * @property-read \ManaPHP\Http\RequestInterface $request
 */
class Tracer extends \ManaPHP\Event\Tracer
{
    public function __construct($options = [])
    {
        parent::__construct($options);

        $this->attachEvent('request:begin', [$this, 'onBegin']);
    }

    public function onBegin()
    {
        $this->logger->debug($this->request->get(), 'http.request');
    }
}