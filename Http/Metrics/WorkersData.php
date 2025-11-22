<?php

declare(strict_types=1);

namespace ManaPHP\Http\Metrics;

use ManaPHP\Coroutine\ContextAware;
use ManaPHP\Coroutine\ContextManagerInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Swoole\WorkersTrait;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use function count;
use function ksort;
use function microtime;

class WorkersData implements WorkersDataInterface, ContextAware
{
    use WorkersTrait;

    #[Autowired] protected ContextManagerInterface $contextManager;
    #[Autowired] protected CollectorFactory $collectorFactory;

    public function getContext(): WorkersDataContext
    {
        return $this->contextManager->getContext($this);
    }

    public function getWorkerRequest(string $collector, int $cid, $worker_id): void
    {
        /** @var WorkersCollectorInterface $workersCollector */
        $workersCollector = $this->collectorFactory->get($collector);
        $this->sendMessage($worker_id)->getWorkerResponse($cid, $this->workers->getWorkerId(), $workersCollector->querying());
    }

    public function getWorkerResponse(int $cid, int $worker_id, array $data): void
    {
        $context = $this->contextManager->getContext($this, $cid);
        $context->data[$worker_id] = $data;
        $context->channel->push(1);
    }

    public function get(string $collector, float $timeout = 1.0): array
    {
        /** @var WorkersCollectorInterface $workersCollector */
        $workersCollector = $this->collectorFactory->get($collector);

        $context = $this->getContext();

        $context->data = [];
        unset($context->channel);

        $worker_num = $this->workers->getWorkerNum();
        $context->channel = new Channel($worker_num);
        $context->data[$this->workers->getWorkerId()] = $workersCollector->querying();
        $context->channel->push(1);

        $my_worker_id = $this->workers->getWorkerId();
        for ($worker_id = 0; $worker_id < $worker_num; $worker_id++) {
            if ($my_worker_id !== $worker_id) {
                $this->sendMessage($worker_id)->getWorkerRequest($workersCollector::class, Coroutine::getCid(), $my_worker_id);
            }
        }

        $end_time = microtime(true) + $timeout;
        do {
            $timeout = $end_time - microtime(true);
            if ($timeout < 0) {
                break;
            }
            $context->channel->pop($timeout);
        } while (count($context->data) < $worker_num);

        ksort($context->data);

        return $context->data;
    }
}
