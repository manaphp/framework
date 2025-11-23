<?php

declare(strict_types=1);

namespace ManaPHP\Exception;

use function error_get_last;

class CreateDirectoryFailedException extends RuntimeException
{
    public function __construct(string $dir)
    {
        $error = error_get_last()['message'] ?? 'Unknown error';
        parent::__construct('Failed to create directory "{dir}": {error}', ['dir' => $dir, 'error' => $error]);
    }
}
