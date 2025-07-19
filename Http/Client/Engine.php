<?php

declare(strict_types=1);

namespace ManaPHP\Http\Client;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\MakerInterface;
use ManaPHP\Http\Client\Engine\Curl;
use ManaPHP\Http\Client\Engine\Fopen;
use function extension_loaded;

class Engine
{
    #[Autowired] protected MakerInterface $maker;

    public function __invoke(array $parameters, ?string $id): mixed
    {
        if (extension_loaded('curl')) {
            return $this->maker->make(Curl::class, $parameters, $id);
        } else {
            return $this->maker->make(Fopen::class, $parameters, $id);
        }
    }
}
