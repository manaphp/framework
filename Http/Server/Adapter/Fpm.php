<?php

declare(strict_types=1);

namespace ManaPHP\Http\Server\Adapter;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\AbstractServer;
use ManaPHP\Http\Server\Adapter\Native\SenderInterface;
use ManaPHP\Http\Server\Event\ServerReady;
use function file_get_contents;

class Fpm extends AbstractServer
{
    #[Autowired] protected SenderInterface $sender;

    protected function prepareGlobals(): void
    {
        $rawBody = file_get_contents('php://input');
        $this->request->prepare($_GET, $_POST, $_SERVER, $rawBody, $_COOKIE, $_FILES);
    }

    public function start(): void
    {
        $this->prepareGlobals();

        $this->bootstrap();

        $this->eventDispatcher->dispatch(new ServerReady(null, $this->host, $this->port));

        $this->requestHandler->handle();
    }

    public function sendHeaders(): void
    {
        $this->sender->sendHeaders();
    }

    public function sendBody(): void
    {
        $this->sender->sendBody();
    }
}
