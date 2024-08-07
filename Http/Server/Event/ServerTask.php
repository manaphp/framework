<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Event;

use JsonSerializable;
use ManaPHP\Eventing\Attribute\Verbosity;
use Swoole\Http\Server;
use function get_class;
use function is_object;

#[Verbosity(Verbosity::MEDIUM)]
class ServerTask implements JsonSerializable
{
    public function __construct(
        public Server $server,
        public int $task_id,
        public int $src_worker_id,
        public mixed $data
    ) {

    }

    public function jsonSerialize(): array
    {
        $type = is_object($this->data) ? get_class($this->data) : 'data';

        return [
            $type           => $this->data,
            'task_id'       => $this->task_id,
            'src_worker_id' => $this->src_worker_id,
        ];
    }
}