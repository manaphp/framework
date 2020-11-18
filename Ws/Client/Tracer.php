<?php

namespace ManaPHP\Ws\Client;

use ManaPHP\Event\EventArgs;

class Tracer extends \ManaPHP\Event\Tracer
{
    public function __construct($options = [])
    {
        parent::__construct($options);

        $this->attachEvent('wsClient:send', [$this, 'onSend']);
        $this->attachEvent('wsClient:recv', [$this, 'onRecv']);
    }

    public function onSend(EventArgs $eventArgs)
    {
        $this->logger->debug($eventArgs->data, 'wsClient.send');
    }

    public function onRecv(EventArgs $eventArgs)
    {
        $this->logger->debug($eventArgs->data, 'wsClient.recv');
    }
}