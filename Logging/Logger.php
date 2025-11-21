<?php

declare(strict_types=1);

namespace ManaPHP\Logging;

use ManaPHP\Coroutine;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Logging\Appender\FileAppender;
use ManaPHP\Logging\Event\LoggerLog;
use ManaPHP\Logging\Message\Categorizable;
use ManaPHP\Text\InterpolatingFormatterInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Stringable;
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
    #[Autowired] protected InterpolatingFormatterInterface $interpolatingFormatter;
    #[Autowired] protected AppenderFactory $appenderFactory;

    #[Autowired] protected string $level = LogLevel::DEBUG;
    #[Autowired] protected array $levels = [];
    #[Autowired] protected ?string $hostname;
    #[Autowired] protected string $time_format = 'Y-m-d\TH:i:s.uP';
    #[Autowired] protected array $appenders = [FileAppender::class];

    public const  MILLISECONDS = 'v';
    public const MICROSECONDS = 'u';

    protected function getCategory(array $context, array $traces): string
    {
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

    protected function format(string|Stringable $message, array $context): string
    {
        if (is_string($message)) {
            if ($context !== [] && str_contains($message, '{')) {
                $message = $this->interpolatingFormatter->interpolate($message, $context);
            }

            if (($exception = $context['exception'] ?? null) !== null && $exception instanceof Throwable) {
                $message .= ': ' . $this->interpolatingFormatter->exceptionToString($exception);
            }
            return $message;
        } elseif ($message instanceof Throwable) {
            return $this->interpolatingFormatter->exceptionToString($message);
        } else {
            return (string)$message;
        }
    }

    public function log($level, mixed $message, array $context = []): void
    {
        if ($this->levels === [] && Level::gt($level, $this->level)) {
            return;
        }

        $traces = Coroutine::getBacktrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 7);
        array_shift($traces);

        if ($message instanceof Categorizable) {
            $category = $message->getCategory();
            $message = (string)$message;
        } else {
            $category = $this->getCategory($context, $traces);
        }

        if ($this->levels !== [] && Level::gt($level, $this->getCategoryLevel($category))) {
            return;
        }

        $log = new Log($level, $this->hostname ?? gethostname(), $this->time_format);
        $log->category = $category;
        $log->setLocation($traces[0]);
        $log->message = $this->format($message, $context);

        $this->eventDispatcher->dispatch(new LoggerLog($this, $level, $message, $context, $log));

        foreach ($this->appenders as $name) {
            $appender = $this->appenderFactory->get($name);
            $appender->append($log);
        }
    }
}
