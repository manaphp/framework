<?php

declare(strict_types=1);

namespace ManaPHP\Debugging;

use ArrayObject;
use JsonSerializable;
use ManaPHP\AliasInterface;
use ManaPHP\Coroutine;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Helper\SuppressWarnings;
use Throwable;
use function basename;
use function count;
use function date;
use function dirname;
use function is_array;
use function is_scalar;
use function is_string;
use function json_stringify;
use function microtime;
use function preg_replace;
use function realpath;
use function rtrim;
use function sprintf;
use function str_contains;
use function strtr;
use function substr_count;

class DataDump implements DataDumpInterface
{
    #[Autowired] protected AliasInterface $alias;

    #[Autowired] protected string $format = '[:time][:location] :message';

    protected function getLocation(array $traces): array
    {
        for ($i = count($traces) - 1; $i >= 0; $i--) {
            $trace = $traces[$i];
            $function = $trace['function'];
            if ($function === 'output') {
                return $traces[$i + 1];
            }
        }

        return [];
    }

    public function exceptionToString(Throwable $exception): string
    {
        $str = $exception::class . ': ' . $exception->getMessage() . PHP_EOL;
        $str .= '    at ' . $exception->getFile() . ':' . $exception->getLine() . PHP_EOL;
        $traces = $exception->getTraceAsString();
        $str .= preg_replace('/#\d+\s/', '    at ', $traces);

        $prev = $traces;
        $caused = $exception;
        while ($caused = $caused->getPrevious()) {
            $str .= PHP_EOL . '  Caused by ' . $caused::class . ': ' . $caused->getMessage() . PHP_EOL;
            $str .= '    at ' . $caused->getFile() . ':' . $caused->getLine() . PHP_EOL;
            $traces = $exception->getTraceAsString();
            if ($traces !== $prev) {
                $str .= preg_replace('/#\d+\s/', '    at ', $traces);
            } else {
                $str .= '    at ...';
            }

            $prev = $traces;
        }

        $replaces = [];
        if ($this->alias->has('@root')) {
            $replaces[dirname(realpath($this->alias->get('@root'))) . DIRECTORY_SEPARATOR] = '';
        }

        return strtr($str, $replaces);
    }

    public function formatMessage(mixed $message): string
    {
        if ($message instanceof Throwable) {
            return $this->exceptionToString($message);
        } elseif ($message instanceof JsonSerializable || $message instanceof ArrayObject) {
            return json_stringify($message, JSON_PARTIAL_OUTPUT_ON_ERROR);
        } elseif (!is_array($message)) {
            return (string)$message;
        }

        if (!isset($message[0]) || !is_string($message[0])) {
            return json_stringify($message, JSON_PARTIAL_OUTPUT_ON_ERROR);
        }

        if (substr_count($message[0], '%') + 1 >= ($count = count($message)) && isset($message[$count - 1])) {
            foreach ($message as $k => $v) {
                if ($k === 0 || is_scalar($v) || $v === null) {
                    continue;
                }

                if ($v instanceof Throwable) {
                    $message[$k] = $this->exceptionToString($v);
                } elseif (is_array($v)) {
                    $message[$k] = json_stringify($v, JSON_PARTIAL_OUTPUT_ON_ERROR);
                } elseif ($v instanceof JsonSerializable || $v instanceof ArrayObject) {
                    $message[$k] = json_stringify($v, JSON_PARTIAL_OUTPUT_ON_ERROR);
                }
            }
            return sprintf(...$message);
        }

        if (count($message) === 2) {
            if (isset($message[1]) && !str_contains($message[0], ':1')) {
                $message[0] = rtrim($message[0], ': ') . ': :1';
            }
        } elseif (count($message) === 3) {
            /** @noinspection NotOptimalIfConditionsInspection */
            if (isset($message[1], $message[2]) && !str_contains($message[0], ':1') && is_scalar($message[1])) {
                $message[0] = rtrim($message[0], ': ') . ': :1 => :2';
            }
        }

        $replaces = [];
        foreach ($message as $k => $v) {
            if ($k === 0) {
                continue;
            }

            if ($v instanceof Throwable) {
                $v = $this->exceptionToString($v);
            } elseif (is_array($v)) {
                $v = json_stringify($v, JSON_PARTIAL_OUTPUT_ON_ERROR);
            } elseif ($v instanceof JsonSerializable || $v instanceof ArrayObject) {
                $v = json_stringify($v, JSON_PARTIAL_OUTPUT_ON_ERROR);
            } elseif (is_string($v)) {
                SuppressWarnings::noop();
            } elseif ($v === null || is_scalar($v)) {
                $v = json_stringify($v, JSON_PARTIAL_OUTPUT_ON_ERROR);
            } else {
                $v = (string)$v;
            }

            $replaces[":$k"] = $v;
        }

        return strtr($message[0], $replaces);
    }

    public function output(mixed $message): void
    {
        if (is_array($message) && count($message) === 1 && isset($message[0])) {
            $message = $message[0];
        }

        if ($message instanceof Throwable) {
            $file = basename($message->getFile());
            $line = $message->getLine();
        } else {
            $traces = Coroutine::getBacktrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 5);
            $location = $this->getLocation($traces);
            if (isset($location['file'])) {
                $file = basename($location['file']);
                $line = $location['line'];
            } else {
                $file = '';
                $line = 0;
            }
        }

        $message = is_string($message) ? $message : $this->formatMessage($message);
        $timestamp = microtime(true);

        $replaced = [];

        $replaced[':time'] = date('H:i:s', (int)$timestamp) . sprintf('.%03d', ($timestamp - (int)$timestamp) * 1000);
        $replaced[':date'] = date('Y-m-d\T', (int)$timestamp) . $replaced[':time'];
        $replaced[':location'] = "$file:$line";
        $replaced[':message'] = $message;

        echo strtr($this->format, $replaced), PHP_EOL;
    }
}
