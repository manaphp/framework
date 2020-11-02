<?php

namespace ManaPHP\Http\Server\Adapter;

use ManaPHP\Exception\MisuseException;
use ManaPHP\Http\Server;

/**
 * Class Fpm
 *
 * @package ManaPHP\Http\Server\Adapter
 */
class Fpm extends Server
{
    protected function _prepareGlobals()
    {
        if (!isset($_GET['_url']) && ($pos = strpos($_SERVER['PHP_SELF'], '/index.php/')) !== false) {
            $_GET['_url'] = $_REQUEST['_url'] = '/index' . substr($_SERVER['PHP_SELF'], $pos + 10);
        }

        $rawBody = file_get_contents('php://input');
        $this->request->prepare($_GET, $_POST, $_SERVER, $rawBody, $_COOKIE, $_SERVER);

        if ($this->_use_globals) {
            $this->globalsManager->proxy();
        }
    }

    /**
     * @param \ManaPHP\Http\Server\HandlerInterface $handler
     *
     * @return static
     */
    public function start($handler)
    {
        $this->_prepareGlobals();

        $handler->handle();

        return $this;
    }

    /**
     * @param \ManaPHP\Http\ResponseContext $response
     *
     * @return static
     */
    public function send($response)
    {
        if (headers_sent($file, $line)) {
            throw new MisuseException("Headers has been sent in $file:$line");
        }

        $this->fireEvent('response:sending', ['response' => $response]);

        header('HTTP/1.1 ' . $response->status_code . ' ' . $response->status_text);

        foreach ($response->headers as $header => $value) {
            if ($value !== null) {
                header($header . ': ' . $value);
            } else {
                header($header);
            }
        }

        foreach ($response->cookies as $cookie) {
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

        $server = $this->request->getContext()->_SERVER;

        header('X-Request-Id: ' . $this->request->getRequestId());
        header('X-Response-Time: ' . sprintf('%.3f', microtime(true) - $server['REQUEST_TIME_FLOAT']));

        if ($response->status_code === 304) {
            null;
        } elseif ($server['REQUEST_METHOD'] === 'HEAD') {
            header('Content-Length: ' . strlen($response->content));
        } elseif ($response->file) {
            readfile($this->alias->resolve($response->file));
        } else {
            echo $response->content;
        }

        $this->fireEvent('response:sent', ['response' => $response]);

        return $this;
    }
}
