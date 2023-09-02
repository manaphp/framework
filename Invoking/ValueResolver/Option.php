<?php
declare(strict_types=1);

namespace ManaPHP\Invoking\ValueResolver;

use ManaPHP\Cli\RequestInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Invoking\ScalarValueResolverInterface;
use ReflectionParameter;

class Option implements ScalarValueResolverInterface
{
    #[Inject] protected RequestInterface $request;

    public function resolve(ReflectionParameter $parameter, ?string $type, string $name): mixed
    {
        if ($this->request->has($name)) {
            return $this->request->get($name, $type === 'array' ? [] : '');
        } else {
            return null;
        }
    }
}