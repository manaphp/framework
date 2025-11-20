<?php
declare(strict_types=1);

namespace ManaPHP\Logging\Message;

interface Categorizable
{
    public function getCategory(): string;
}