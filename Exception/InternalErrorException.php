<?php

declare(strict_types=1);

namespace ManaPHP\Exception;

use ManaPHP\Exception;

class InternalErrorException extends Exception
{
    public function __construct(string $message = 'Internal server error. Please try again later.')
    {
        parent::__construct($message);

        $this->json = [
            'code' => 500,
            'msg'  => $this->getMessage()
        ];
    }
}
