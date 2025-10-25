<?php

declare(strict_types=1);

namespace ManaPHP\Logging;

use ManaPHP\Coroutine;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Helper\Container;
use ManaPHP\Logging\Appender\FileAppender;
use ManaPHP\Logging\Event\LoggerLog;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Throwable;
use function array_shift;
use function gethostname;
use function is_string;
use function str_contains;
use function str_ends_with;
use function str_replace;
use function strlen;
use function strrpos;
use function substr;

class Logger extends AbstractLogger
{
    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected MessageFormatterInterface $messageFormatter;

    #[Autowired] protected string $level = LogLevel::DEBUG;
    #[Autowired] protected array $levels = [];
    #[Autowired] protected ?string $hostname;
    #[Autowired] protected string $time_format = 'Y-m-d\TH:i:s.uP';
    #[Autowired] protected array $appenders = [FileAppender::class];

    public const  MILLISECONDS = 'v';
    public const MICROSECONDS = 'u';

    public function __construct()
    {
        foreach ($this->appenders as $index => $appender) {
            if (is_string($appender)) {
                $this->appenders[$index] = Container::make($appender);
            } else {
                $this->appenders[$index] = Container::make($appender['class'], $appender);
            }
        }
    }

    protected function getCategory(mixed $message, array $context, array $traces): string
    {
        if (($v = $context['category'] ?? null) !== null
            && is_string($v)
            && (!is_string($message) || !str_contains($message, '{category}'))
        ) {
            return $v;
        } else {
            if (($v = $context['exception'] ?? null) !== null && $v instanceof Throwable) {
                $trace = $v->getTrace()[0];
            } elseif (isset($traces[1])) {
                $trace = $traces[1];
                if (str_ends_with($trace['function'], '{closure}')) {
                    $trace = $traces[2];
                }
            } else {
                $trace = $traces[0];
            }
            if (isset($trace['class'])) {
                return str_replace('\\', '.', $trace['class']) . '.' . $trace['function'];
            } else {
                return $trace['function'];
            }
        }
    }

    protected function getCategoryLevel(string $category): string
    {
        if (($level = $this->levels[$category] ?? null) !== null) {
            return $level;
        }

        $prev = 0;
        $len = strlen($category);
        while (($next = strrpos($category, '.', $prev)) > 0) {
            $s = substr($category, 0, $next);
            if (($level = $this->levels[$s] ?? null) !== null) {
                return $level;
            }
            $prev = $next - $len - 1;
        }

        return $this->level;
    }

    public function log($level, mixed $message, array $context = []): void
    {
        if ($this->levels === [] && Level::gt($level, $this->level)) {
            return;
        }

        $traces = Coroutine::getBacktrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 7);
        array_shift($traces);

        $category = $this->getCategory($message, $context, $traces);

        if ($this->levels !== [] && Level::gt($level, $this->getCategoryLevel($category))) {
            return;
        }

        $log = new Log($level, $this->hostname ?? gethostname(), $this->time_format);
        $log->category = $category;
        $log->setLocation($traces[0]);
        $log->message = $this->messageFormatter->format($message, $context);

        $this->eventDispatcher->dispatch(new LoggerLog($this, $level, $message, $context, $log));

        foreach ($this->appenders as $appender) {
            $appender->append($log);
        }
    }
}
