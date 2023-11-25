<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Context\ContextorInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\Metrics\CollectorInterface;
use ManaPHP\Http\Metrics\FormatterInterface;
use ManaPHP\Swoole\WorkersTrait;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;

class CoroutineStatsCollector implements CollectorInterface
{
    use WorkersTrait;

    #[Autowired] protected ContextorInterface $contextor;
    #[Autowired] protected FormatterInterface $formatter;

    public function getContext(int $cid = 0): CoroutineStatsCollectorContext
    {
        return $this->contextor->getContext($this, $cid);
    }

    public function getRequest(int $cid, int $worker_id): void
    {
        $my_worker_id = $this->workers->getWorkerId();
        $stats = Coroutine::stats();
        $this->sendMessage($worker_id)->getResponse($cid, $my_worker_id, $stats);
    }

    public function getResponse(int $cid, $worker_id, array $stats): void
    {
        $context = $this->getContext($cid);
        $context->stats[$worker_id] = $stats;
        $context->channel->push(1);
    }

    public function export(): string
    {
        $context = $this->getContext();
        $worker_num = $this->workers->getWorkerNum();
        $context->channel = new Channel($worker_num);
        $context->stats[$this->workers->getWorkerId()] = Coroutine::stats();
        $context->channel->push(1);

        for ($worker_id = 0; $worker_id < $worker_num; $worker_id++) {
            if ($this->workers->getWorkerId() !== $worker_id) {
                $this->sendMessage($worker_id)->getRequest(Coroutine::getCid(), $this->workers->getWorkerId());
            }
        }

        $end_time = microtime(true) + 0.3;
        do {
            $timeout = $end_time - microtime(true);
            if ($timeout < 0) {
                break;
            }
            $context->channel->pop($timeout);
        } while (\count($context->stats) < $worker_num);

        $types = [
            'event_num'           => FormatterInterface::GAUGE,
            'signal_listener_num' => FormatterInterface::GAUGE,
            'aio_task_num'        => FormatterInterface::GAUGE,
            'aio_worker_num'      => FormatterInterface::GAUGE,
            'c_stack_size'        => FormatterInterface::GAUGE,
            'coroutine_num'       => FormatterInterface::GAUGE,
            'coroutine_peak_num'  => FormatterInterface::GAUGE,
            'coroutine_last_cid'  => FormatterInterface::GAUGE,
        ];

        ksort($context->stats);

        $str = '';
        foreach ($context->stats as $worker_id => $stats) {
            foreach ($types as $name => $type) {
                if (!isset($stats[$name])) {
                    continue;
                }

                if ($type === FormatterInterface::GAUGE) {
                    $str .= $this->formatter->gauge(
                        'swoole_coroutine_stats_' . $name, $stats[$name], ['worker_id' => $worker_id]
                    );
                }
            }
        }

        return $str;
    }
}