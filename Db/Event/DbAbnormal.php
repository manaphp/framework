<?php

declare(strict_types=1);

namespace ManaPHP\Db\Event;

use ManaPHP\Db\ConnectionInterface;
use ManaPHP\Eventing\Attribute\TraceLevel;
use PDO;
use Psr\Log\LogLevel;

#[TraceLevel(LogLevel::WARNING)]
class DbAbnormal
{
    public function __construct(
        public ConnectionInterface $connection,
        public string $dsn,
        public string $uri,
        public PDO $pdo,
    ) {

    }
}
