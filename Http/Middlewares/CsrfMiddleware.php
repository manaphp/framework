<?php
declare(strict_types=1);

namespace ManaPHP\Http\Middlewares;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\Middlewares\CsrfMiddleware\AttackDetectedException;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\Server\Event\RequestValidating;
use ManaPHP\Mvc\Controller as MvcController;
use ManaPHP\Mvc\View\Attribute\ViewGetMapping;
use ManaPHP\Mvc\ViewInterface;
use ManaPHP\Rest\Controller as RestController;
use ReflectionAttribute;
use ReflectionMethod;
use function in_array;

class CsrfMiddleware
{
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ViewInterface $view;

    #[Autowired] protected bool $strict = true;
    #[Autowired] protected array $domains = [];

    protected function isOriginSafe(): bool
    {
        if (($origin = $this->request->origin(false)) === '') {
            return false;
        }

        if (($host = $this->request->header('host')) === null) {
            return false;
        }

        if (($pos = strpos($origin, '://')) === false) {
            return false;
        }
        $origin_domain = substr($origin, $pos + 3);

        if ($origin_domain === $host) {
            return true;
        }

        if ($domains = $this->domains) {
            if (in_array($origin_domain, $domains, true)) {
                return true;
            }

            foreach ($domains as $domain) {
                if ($domain[0] === '*') {
                    if (str_ends_with($origin_domain, substr($domain, 1))) {
                        return true;
                    }
                } elseif (str_contains($domain, '^') && str_contains($domain, '$')) {
                    if (preg_match($origin_domain, $domain) === 1) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function onValidating(#[Event] RequestValidating $event): void
    {
        if ($this->isOriginSafe()) {
            return;
        }

        $controller = $event->controller;

        if ($controller instanceof RestController) {
            return;
        }

        if ($this->request->method() === 'GET') {
            if (!$this->strict) {
                return;
            }

            if ($controller instanceof MvcController && !$this->request->isAjax()) {
                $rMethod = new ReflectionMethod($controller, $event->action);
                if ($rMethod->getAttributes(ViewGetMapping::class, ReflectionAttribute::IS_INSTANCEOF) !== []) {
                    return;
                }
            }
        }

        throw new AttackDetectedException();
    }
}