<?php
declare(strict_types=1);

namespace ManaPHP\Ws;

use ManaPHP\Coroutine\Context\Stickyable;

class ConnCtxContext implements Stickyable
{
    public array $data = [];
}