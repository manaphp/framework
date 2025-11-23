<?php

/** @noinspection PhpUndefinedNamespaceInspection */
/** @noinspection PhpUndefinedClassInspection */
declare(strict_types=1);

namespace ManaPHP\Http\Server\Adapter;

use ManaPHP\Coroutine\ContextAware;
use ManaPHP\Coroutine\ContextManagerInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\AbstractServer;
use ManaPHP\Http\Server\Event\ServerReady;
use Throwable;
use Workerman\Connection\ConnectionInterface;
use Workerman\Protocols\Http;
use Workerman\Worker;
use function basename;
use function dirname;
use function json_stringify;
use function microtime;
use function str_contains;
use function str_repeat;
use function strlen;

class Workerman extends AbstractServer implements ContextAware
{
    #[Autowired] protected ContextManagerInterface $contextManager;

    #[Autowired] protected array $settings = [];

    protected Worker $worker;
    protected array $_SERVER = [];
    protected int $max_request;
    protected int $request_count;

    public function __construct()
    {
        $script_filename = get_included_files()[0];
        $this->_SERVER = [
            'DOCUMENT_ROOT'   => dirname($script_filename),
            'SCRIPT_FILENAME' => $script_filename,
            'SCRIPT_NAME'     => '/' . basename($script_filename),
            'SERVER_ADDR'     => $this->host,
            'PHP_SELF'        => '/' . basename($script_filename),
            'QUERY_STRING'    => '',
            'REQUEST_SCHEME'  => 'http',
        ];

        unset($_GET, $_POST, $_REQUEST, $_FILES, $_COOKIE);
    }

    public function getContext(): WorkermanContext
    {
        return $this->contextManager->getContext($this);
    }

    protected function prepareGlobals(): void
    {
        $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);

        $_SERVER += $this->_SERVER;

        $raw_body = $GLOBALS['HTTP_RAW_POST_DATA'] ?? null;
        $this->request->prepare($_GET, $_POST, $_SERVER, $raw_body, $_COOKIE, $_FILES);

        unset($_GET, $_POST, $_REQUEST, $_FILES, $_COOKIE);
        foreach ($_SERVER as $k => $v) {
            if (!str_contains('DOCUMENT_ROOT,SERVER_SOFTWARE,SCRIPT_NAME,SCRIPT_FILENAME', $k)) {
                unset($_SERVER[$k]);
            }
        }
    }

    public function start(): void
    {
        echo PHP_EOL, str_repeat('+', 80), PHP_EOL;

        $this->bootstrap();

        /** @noinspection HttpUrlsUsage */
        $this->worker = $worker = new Worker("http://$this->host:$this->port");

        $settings = json_stringify($this->settings);
        console_log('info', 'listen on: {host}:{port} with setting: {settings}', ['host' => $this->host, 'port' => $this->port, 'settings' => $settings]);
        echo 'ab';
        $worker->onMessage = [$this, 'onRequest'];

        if (isset($this->settings['worker_num'])) {
            $worker->count = (int)$this->settings['worker_num'];
        }

        global $argv;

        $argv[1] ??= 'start';

        if (DIRECTORY_SEPARATOR === '\\') {
            shell_exec("explorer.exe http://127.0.0.1:$this->port/" . $this->router->getPrefix());
        }

        $this->eventDispatcher->dispatch(new ServerReady(null, $this->host, $this->port));

        Worker::runAll();

        console_log('info', 'shutdown');
    }

    public function onRequest(ConnectionInterface $connection): void
    {
        $this->prepareGlobals();

        try {
            $context = $this->getContext();
            $context->connection = $connection;
            $this->requestHandler->handle();
        } catch (Throwable $throwable) {
            echo $this->formatException($throwable);
        }

        $this->contextManager->resetContexts();

        if ($this->max_request && ++$this->request_count >= $this->max_request) {
            Worker::stopAll();
        }
    }

    public function sendHeaders(): void
    {
        Http::header('HTTP', true, $this->response->getStatusCode());

        foreach ($this->response->getHeaders() as $name => $value) {
            Http::header("$name: $value");
        }

        $prefix = $this->router->getPrefix();
        foreach ($this->response->getCookies() as $cookie) {
            Http::setcookie(
                $cookie['name'],
                $cookie['value'],
                $cookie['expire'],
                $cookie['path'] === '' ? '' : ($prefix . $cookie['path']),
                $cookie['domain'],
                $cookie['secure'],
                $cookie['httponly']
            );
        }
    }

    public function sendBody(): void
    {
        $context = $this->getContext();
        $content = $this->response->getContent() ?? '';
        if ($this->response->getStatusCode() === 304) {
            $context->connection->close('');
        } elseif ($this->request->method() === 'HEAD') {
            Http::header('Content-Length: ' . strlen($content));
            $context->connection->close('');
        } else {
            $context->connection->close($content);
        }
    }
}
