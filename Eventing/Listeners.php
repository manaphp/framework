<?php

declare(strict_types=1);

namespace ManaPHP\Eventing;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Helper\LocalFS;
use function array_shift;
use function count;
use function explode;
use function is_int;
use function preg_match;
use function sprintf;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function strpos;
use function substr;
use function substr_count;

class Listeners implements ListenersInterface
{
    #[Autowired] protected ListenerProviderInterface $listenerProvider;
    #[Autowired] protected array $listeners
        = [
            '@app/Listeners/*.php'         => 'App\Listeners\*',
            '@app/Areas/*/Listeners/*.php' => 'App\Areas\*\Listeners\*',
        ];

    public function bootstrap(): void
    {
        foreach ($this->listeners as $glob => $class) {
            if (is_int($glob)) {
                $this->listenerProvider->add($class);
            } else {
                foreach (LocalFS::glob($glob) as $file) {
                    if (str_ends_with($file, 'Context.php')) {
                        continue;
                    }

                    if (DIRECTORY_SEPARATOR === '\\') {
                        $file = str_replace('\\', '/', $file);
                    }

                    if (str_starts_with($glob, '@')) {
                        list(, $left) = explode('/', $glob, 2);
                    } else {
                        $left = $glob;
                    }
                    $pattern = '#' . str_replace('*', '(\w+)', $left) . '#';

                    $listener = $class;
                    if (preg_match($pattern, $file, $matches) === 1) {
                        array_shift($matches);

                        if (count($matches) !== substr_count($listener, '*')) {
                            throw new InvalidValueException(sprintf('%s glob with %s class', $glob, $class));
                        }

                        while (($pos = strpos($listener, '*')) !== false) {
                            $placeholder = array_shift($matches);
                            $listener = substr($listener, 0, $pos) . $placeholder . substr($listener, $pos + 1);
                        }
                    }
                    $this->listenerProvider->add($listener);
                }
            }
        }
    }
}
