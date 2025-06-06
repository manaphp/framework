<?php

declare(strict_types=1);

namespace ManaPHP\Swoole;

use ManaPHP\BootstrapperInterface;
use Swoole\Server;

interface WorkersInterface extends BootstrapperInterface
{
    public function task(array|callable $task, array $arguments, int $task_worker_id, ?float $timeout = null): mixed;

    public function sendMessage(array|callable $task, array $arguments, int $dst_worker_id): bool;

    public function getWorkerId(): int;

    public function getWorkerNum(): int;

    public function getTaskWorkerNum(): int;

    public function getServer(): Server;
}
