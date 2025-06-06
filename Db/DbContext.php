<?php

declare(strict_types=1);

namespace ManaPHP\Db;

use ManaPHP\Coroutine\ContextInseparable;
use ManaPHP\Exception\MisuseException;

class DbContext implements ContextInseparable
{
    public ?ConnectionInterface $connection = null;
    public int $transaction_level = 0;

    public function __destruct()
    {
        if ($this->transaction_level !== 0) {
            throw new MisuseException('transaction is not close correctly');
        }

        if ($this->connection !== null) {
            throw new MisuseException('connection is not released to pool');
        }
    }
}
