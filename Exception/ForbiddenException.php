<?php
declare(strict_types=1);

namespace ManaPHP\Exception;

use ManaPHP\Exception;

class ForbiddenException extends Exception
{
    public function getStatusCode(): int
    {
        return 403;
    }
}