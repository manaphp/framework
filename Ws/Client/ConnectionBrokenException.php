<?php

declare(strict_types=1);

namespace ManaPHP\Ws\Client;

class ConnectionBrokenException extends Exception
{
    public function __construct($message = 'connection is broken')
    {
        parent::__construct($message);
    }
}
