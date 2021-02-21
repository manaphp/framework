<?php

namespace ManaPHP\Http\Server\Adapter;

use ManaPHP\Exception\MisuseException;
use ManaPHP\Http\Server;

/**
 * @property-read \ManaPHP\AliasInterface $alias
 */
class Fpm extends Server
{
    /**
     * @return void
     */
    protected function prepareGlobals()
    {
        $rawBody = file_get_contents('php://input');
        $this->request->prepare($_GET, $_POST, $_SERVER, $rawBody, $_COOKIE, $_FILES);

        if ($this->use_globals) {
            $this->globalsManager->proxy();
        }
    }

    /**
     * @param \ManaPHP\Http\Server\HandlerInterface $handler
     *
     * @return void
     */
    public function start($handler)
    {
        $this->prepareGlobals();

        $handler->handle();
    }

    /**
     * @return static
     */
    public function send()
    {
        if (headers_sent($file, $line)) {
            throw new MisuseException("Headers has been sent in $file:$line");
        }

        $context = $this->response->getContext();

        if (!is_string($context->content) && !$context->file) {
            $this->fireEvent('response:stringify');
            if (!is_string($context->content)) {
                $context->content = json_stringify($context->content);
            }
        }

        $this->fireEvent('response:sending');

        header('HTTP/1.1 ' . $this->response->getStatus());

        foreach ($context->headers as $header => $value) {
            if ($value !== null) {
                header($header . ': ' . $value);
            } else {
                header($header);
            }
        }

        foreach ($context->cookies as $cookie) {
            setcookie(
                $cookie['name'],
                $cookie['value'],
                $cookie['expire'],
                $cookie['path'],
                $cookie['domain'],
                $cookie['secure'],
                $cookie['httponly']
            );
        }

        header('X-Request-Id: ' . $this->request->getRequestId());
        header('X-Response-Time: ' . $this->request->getElapsedTime());

        if ($this->response->getStatusCode() === 304) {
            null;
        } elseif ($this->request->isHead()) {
            header('Content-Length: ' . strlen($context->content));
        } elseif ($context->file) {
            readfile($this->alias->resolve($context->file));
        } else {
            echo $context->content;
        }

        $this->fireEvent('response:sent');

        return $this;
    }
}
