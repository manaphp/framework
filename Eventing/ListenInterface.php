<?php
declare(strict_types=1);

namespace ManaPHP\Eventing;

interface ListenInterface
{
    public function listen(): void;
}