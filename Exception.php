<?php

declare(strict_types=1);

namespace ManaPHP;

use Throwable;
use function is_string;
use function json_stringify;
use function str_contains;
use function strtr;

class Exception extends \Exception
{
    protected array $context = [];
    protected array $json = [];

    public function __construct(string|Throwable $message = '', array $context = [], int $code = 0, ?\Exception $previous = null)
    {
        if ($message instanceof Throwable) {
            $this->context = $context;
            parent::__construct($message->getMessage(), $code, $message);
        } else {
            if ($context !== []) {
                $tr = [];
                $extra = [];
                foreach ($context as $k => $v) {
                    if (str_contains($message, "{{$k}}")) {
                        $tr["{{$k}}"] = is_string($v) ? $v : json_stringify($v);
                    } else {
                        $extra[$k] = $v;
                    }
                }
                $message = strtr($message, $tr);
                $this->context = $extra;
            }

            parent::__construct($message, $code, $previous);
        }
    }

    public function getStatusCode(): int
    {
        return 500;
    }

    public function getJson(): array
    {
        if ($this->json) {
            return $this->json;
        } else {
            $code = $this->getStatusCode();
            $message = $code === 500 ? 'Server Internal Error' : $this->getMessage();
            return ['code' => $code === 200 ? -1 : $code, 'msg' => $message];
        }
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
