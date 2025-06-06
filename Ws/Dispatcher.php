<?php

declare(strict_types=1);

namespace ManaPHP\Ws;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\ListenerProviderInterface;
use ManaPHP\Ws\Server\Event\ServerStop;
use function method_exists;
use function spl_object_id;

class Dispatcher implements DispatcherInterface
{
    #[Autowired] protected ListenerProviderInterface $listenerProvider;

    protected array $controllers;

    public function invokeAction(object $controller, string $action): mixed
    {
        $controller_oid = spl_object_id($controller);
        if (!isset($this->controllers[$controller_oid])) {
            $this->controllers[$controller_oid] = true;

            if (method_exists($controller, 'startAction')) {
                $controller->startAction();
            }

            if (method_exists($controller, 'stopAction')) {
                $this->listenerProvider->on(ServerStop::class, [$controller, 'stopAction']);
            }
        }

        if (!method_exists($controller, $action . 'Action')) {
            return null;
        }

        return $controller->invoke($action);
    }
}
