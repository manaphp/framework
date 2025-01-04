<?php

declare(strict_types=1);

namespace ManaPHP\Http\Server\Listeners;

use ManaPHP\Di\Attribute\Config;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Helper\SuppressWarnings;
use ManaPHP\Http\Server\Event\ServerManagerStart;
use ManaPHP\Http\Server\Event\ServerStart;
use ManaPHP\Http\Server\Event\ServerWorkerStart;
use function cli_set_process_title;
use function sprintf;

class RenameProcessTitleListener
{
    #[Config] protected string $app_id;

    public function onServerStart(#[Event] ServerStart $event): void
    {
        SuppressWarnings::unused($event);

        @cli_set_process_title(sprintf('%s.swoole-master', $this->app_id));
    }

    public function onServerManagerStart(#[Event] ServerManagerStart $event): void
    {
        SuppressWarnings::unused($event);

        @cli_set_process_title(sprintf('%s.swoole-manager', $this->app_id));
    }

    public function onServerWorkerStart(#[Event] ServerWorkerStart $event): void
    {
        $worker_num = $event->worker_num;
        $worker_id = $event->worker_id;

        if ($worker_id < $worker_num) {
            @cli_set_process_title(sprintf('%s.swoole-worker.%d', $this->app_id, $worker_id));
        } else {
            $tasker_id = $worker_id - $worker_num;
            @cli_set_process_title(sprintf('%s.swoole-worker.%d.%d', $this->app_id, $worker_id, $tasker_id));
        }
    }
}