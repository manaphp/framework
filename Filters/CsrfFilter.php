<?php
declare(strict_types=1);

namespace ManaPHP\Filters;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Filters\CsrfFilter\AttackDetectedException;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\Server\Event\RequestValidating;
use ManaPHP\Mvc\Controller as MvcController;
use ManaPHP\Mvc\ViewInterface;
use ManaPHP\Rest\Controller as RestController;

class CsrfFilter
{
    #[Inject] protected RequestInterface $request;
    #[Inject] protected ViewInterface $view;

    #[Value] protected bool $strict = true;
    #[Value] protected array $domains = [];

    protected function isOriginSafe(): bool
    {
        if (($origin = $this->request->getOrigin(false)) === '') {
            return false;
        }

        if (($host = $this->request->getHost()) === '') {
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

        if ($this->request->isGet()) {
            if (!$this->strict) {
                return;
            }

            if ($controller instanceof MvcController
                && !$this->request->isAjax()
                && $this->view->exists()
            ) {
                return;
            }
        }

        throw new AttackDetectedException();
    }
}