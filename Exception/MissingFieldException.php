<?php

declare(strict_types=1);

namespace ManaPHP\Exception;

use function str_contains;

class MissingFieldException extends RuntimeException
{
    public function __construct(string $message = '', array $context = [])
    {
        if (!str_contains($message, ' ')) {
            $field = $message ?: ($context['field'] ?? '');
            $message = 'Missing "{field}" field.';
            $context['field'] = $field;
        }
        parent::__construct($message, $context);
    }
}
