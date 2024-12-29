<?php

declare(strict_types=1);

namespace ManaPHP\Rest;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\AbstractHandler;
use Throwable;

class Handler extends AbstractHandler
{
    #[Autowired] protected ErrorHandlerInterface $errorHandler;

    protected function handleError(Throwable $throwable): void
    {
        $this->errorHandler->handle($throwable);
    }
}
