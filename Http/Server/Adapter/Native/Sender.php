<?php

declare(strict_types=1);

namespace ManaPHP\Http\Server\Adapter\Native;

use ManaPHP\AliasInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\SuppressWarnings;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Http\RouterInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use function strlen;

class Sender implements SenderInterface
{
    #[Autowired] protected ContainerInterface $container;
    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected AliasInterface $alias;
    #[Autowired] protected RouterInterface $router;

    public function send(): void
    {
        if (headers_sent($file, $line)) {
            throw new MisuseException("Headers has been sent in $file:$line");
        }

        header('HTTP/1.1 ' . $this->response->getStatus());

        foreach ($this->response->getHeaders() as $header => $value) {
            if ($value !== null) {
                header($header . ': ' . $value);
            } else {
                header($header);
            }
        }

        $prefix = $this->router->getPrefix();
        foreach ($this->response->getCookies() as $cookie) {
            setcookie(
                $cookie['name'],
                $cookie['value'],
                $cookie['expire'],
                $cookie['path'] === '' ? '' : ($prefix . $cookie['path']),
                $cookie['domain'],
                $cookie['secure'],
                $cookie['httponly']
            );
        }

        $content = $this->response->getContent() ?? '';
        if ($this->response->getStatusCode() === 304) {
            SuppressWarnings::noop();
        } elseif ($this->request->method() === 'HEAD') {
            header('Content-Length: ' . strlen($content));
        } elseif ($file = $this->response->getFile()) {
            readfile($this->alias->resolve($file));
        } else {
            echo $content;
        }
    }
}
