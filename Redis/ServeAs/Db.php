<?php
namespace ManaPHP\Redis\ServeAs;

use ManaPHP\Redis;

class Db extends Redis
{
    public function __construct($uri = null)
    {
        $this->_serve_as = self::SERVE_AS_DB;

        parent::__construct($uri);
    }
}
