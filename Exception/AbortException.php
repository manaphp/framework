<?php

declare(strict_types=1);

namespace ManaPHP\Exception;

use ManaPHP\Exception;

class AbortException extends Exception
{
    public function __construct($message = 'The process was terminated prematurely.', $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
