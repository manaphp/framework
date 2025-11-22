<?php

declare(strict_types=1);

namespace ManaPHP\Exception;

use JetBrains\PhpStorm\ArrayShape;
use ManaPHP\Exception;

class TooManyRequestsException extends Exception
{
    public function __construct($message = 'You have exceeded the allowed number of requests. Please try again later.',
                                $code = 0, $previous = null
    )
    {
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        /**
         * https://tools.ietf.org/html/rfc6585#section-4
         */
        return 429;
    }

    #[ArrayShape(['code' => 'int', 'msg' => 'string'])]
    public function getJson(): array
    {
        return ['code' => 429, 'msg' => 'Too Many Request'];
    }
}
