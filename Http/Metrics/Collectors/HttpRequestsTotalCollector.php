<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\DispatcherInterface;
use ManaPHP\Http\Metrics\CollectorInterface;
use ManaPHP\Http\Metrics\FormatterInterface;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Http\Server\Event\RequestEnd;
use ManaPHP\Swoole\WorkersTrait;

class HttpRequestsTotalCollector implements CollectorInterface
{
    use WorkersTrait;

    #[Autowired] protected FormatterInterface $formatter;
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected DispatcherInterface $dispatcher;

    #[Autowired] protected int $tasker_id = 0;

    protected array $totals = [];

    public function updateRequest(int $code, string $handler): void
    {
        if (!isset($this->totals[$code][$handler])) {
            $this->totals[$code][$handler] = 0;
        } else {
            $this->totals[$code][$handler]++;
        }
    }

    public function getResponse(): array
    {
        return $this->totals;
    }

    public function onRequestEnd(#[Event] RequestEnd $event): void
    {
        if (($handler = $this->dispatcher->getHandler()) !== null) {
            $code = $this->response->getStatusCode();

            $this->task($this->tasker_id)->updateRequest($code, $handler);
        }
    }

    public function export(): string
    {
        $totals = $this->task($this->tasker_id, 0.1)->getResponse();

        return $this->formatter->counter('app_http_requests_total', $totals, [], ['code', 'handler']);
    }
}