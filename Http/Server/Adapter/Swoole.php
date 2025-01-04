<?php

declare(strict_types=1);

namespace ManaPHP\Http\Server\Adapter;

use ManaPHP\AliasInterface;
use ManaPHP\Coroutine\ContextAware;
use ManaPHP\Coroutine\ContextManagerInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Di\ConfigInterface;
use ManaPHP\Di\Lazy;
use ManaPHP\Helper\Ip;
use ManaPHP\Http\AbstractServer;
use ManaPHP\Http\RouterInterface;
use ManaPHP\Http\Server\Event\ServerBeforeShutdown;
use ManaPHP\Http\Server\Event\ServerClose;
use ManaPHP\Http\Server\Event\ServerConnect;
use ManaPHP\Http\Server\Event\ServerFinish;
use ManaPHP\Http\Server\Event\ServerManagerStart;
use ManaPHP\Http\Server\Event\ServerManagerStop;
use ManaPHP\Http\Server\Event\ServerPacket;
use ManaPHP\Http\Server\Event\ServerPipeMessage;
use ManaPHP\Http\Server\Event\ServerReady;
use ManaPHP\Http\Server\Event\ServerShutdown;
use ManaPHP\Http\Server\Event\ServerStart;
use ManaPHP\Http\Server\Event\ServerTask;
use ManaPHP\Http\Server\Event\ServerTaskerStart;
use ManaPHP\Http\Server\Event\ServerTaskerStop;
use ManaPHP\Http\Server\Event\ServerWorkerError;
use ManaPHP\Http\Server\Event\ServerWorkerExit;
use ManaPHP\Http\Server\Event\ServerWorkerStart;
use ManaPHP\Http\Server\Event\ServerWorkerStop;
use ManaPHP\Http\Server\StaticHandlerInterface;
use ManaPHP\Swoole\Attribute\ServerCallback;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionMethod;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Swoole\Runtime;
use Throwable;
use function dirname;
use function in_array;
use function strlen;
use function substr;

class Swoole extends AbstractServer implements ContextAware
{
    #[Autowired] protected ContextManagerInterface $contextManager;
    #[Autowired] protected AliasInterface $alias;
    #[Autowired] protected StaticHandlerInterface|Lazy $staticHandler;
    #[Autowired] protected ConfigInterface $config;
    #[Autowired] protected LoggerInterface $logger;

    #[Autowired] protected array $settings = [];

    #[Config] protected string $app_id;

    protected array $_SERVER;

    public function __construct()
    {
        $script_filename = get_included_files()[0];
        $document_root = dirname($script_filename);
        $_SERVER['DOCUMENT_ROOT'] = $document_root;

        $this->_SERVER = [
            'DOCUMENT_ROOT'   => $document_root,
            'SCRIPT_FILENAME' => $script_filename,
            'SCRIPT_NAME'     => '/' . basename($script_filename),
            'SERVER_ADDR'     => $this->host === '0.0.0.0' ? Ip::local() : $this->host,
            'SERVER_PORT'     => $this->port,
            'SERVER_SOFTWARE' => 'Swoole/' . SWOOLE_VERSION . ' (' . PHP_OS . ') PHP/' . PHP_VERSION,
            'PHP_SELF'        => '/' . basename($script_filename),
            'QUERY_STRING'    => '',
            'REQUEST_SCHEME'  => 'http',
        ];

        $this->settings['enable_coroutine'] = MANAPHP_COROUTINE_ENABLED;

        if (isset($this->settings['max_request']) && $this->settings['max_request'] < 1) {
            $this->settings['max_request'] = 1;
        }

        if (!empty($this->settings['enable_static_handler'])) {
            $this->settings['document_root'] = $document_root;
        }
    }

    public function getContext(): SwooleContext
    {
        return $this->contextManager->getContext($this);
    }

    protected function prepareGlobals(Request $request): void
    {
        $_server = array_change_key_case($request->server, CASE_UPPER);
        unset($_server['SERVER_SOFTWARE']);

        foreach ($request->header ?: [] as $k => $v) {
            if (in_array($k, ['content-type', 'content-length'], true)) {
                $_server[strtoupper(strtr($k, '-', '_'))] = $v;
            } else {
                $_server['HTTP_' . strtoupper(strtr($k, '-', '_'))] = $v;
            }
        }

        $_server += $this->_SERVER;

        $_get = $request->get ?: [];
        $_post = $request->post ?: [];
        $raw_body = $request->rawContent();
        $cookies = $request->cookie ?? [];
        $files = $request->files ?? [];

        $this->request->prepare($_get, $_post, $_server, $raw_body, $cookies, $files);
    }

    protected function dispatchEvent(object $object): void
    {
        try {
            $this->eventDispatcher->dispatch($object);
        } catch (Throwable $throwable) {
            $this->logger->error($throwable->getMessage(), ['exception' => $throwable]);
        }
    }

    #[ServerCallback]
    public function onStart(Server $server): void
    {
        @cli_set_process_title(sprintf('%s.swoole-master', $this->app_id));

        $this->dispatchEvent(new ServerStart($server));
    }

    #[ServerCallback]
    public function onBeforeShutdown(Server $server): void
    {
        $this->dispatchEvent(new ServerBeforeShutdown($server));
    }

    #[ServerCallback]
    public function onShutdown(Server $server): void
    {
        $this->dispatchEvent(new ServerShutdown($server));
    }

    #[ServerCallback]
    public function onManagerStart(Server $server): void
    {
        @cli_set_process_title(sprintf('%s.swoole-manager', $this->app_id));

        $this->dispatchEvent(new ServerManagerStart($server));
    }

    #[ServerCallback]
    public function onWorkerStart(Server $server, int $worker_id): void
    {
        $worker_num = $server->setting['worker_num'];
        if ($worker_id < $worker_num) {
            @cli_set_process_title(sprintf('%s.swoole-worker.%d', $this->app_id, $worker_id));
        } else {
            $tasker_id = $worker_id - $worker_num;
            @cli_set_process_title(sprintf('%s.swoole-worker.%d.%d', $this->app_id, $worker_id, $tasker_id));
            $this->dispatchEvent(new ServerTaskerStart($server, $worker_id, $tasker_id));
        }

        $this->dispatchEvent(new ServerWorkerStart($server, $worker_id, $worker_num));
    }

    #[ServerCallback]
    public function onWorkerStop(Server $server, int $worker_id): void
    {
        $worker_num = $server->setting['worker_num'];
        if ($worker_id >= $worker_num) {
            $this->dispatchEvent(new ServerTaskerStop($server, $worker_id, $worker_id - $worker_num));
        }
        $this->dispatchEvent(new ServerWorkerStop($server, $worker_id, $worker_num));
    }

    #[ServerCallback]
    public function onWorkerExit(Server $server, int $worker_id): void
    {
        $this->dispatchEvent(new ServerWorkerExit($server, $worker_id));
    }

    #[ServerCallback]
    public function onConnect(Server $server, int $fd, int $reactor_id): void
    {
        $this->dispatchEvent(new ServerConnect($server, $fd, $reactor_id));
    }

    #[ServerCallback]
    public function onPacket(Server $server, string $data, array $client): void
    {
        $this->dispatchEvent(new ServerPacket($server, $data, $client));
    }

    #[ServerCallback]
    public function onClose(Server $server, int $fd, int $reactor_id): void
    {
        $this->dispatchEvent(new ServerClose($server, $fd, $reactor_id));
    }

    #[ServerCallback]
    public function onTask(Server $server, int $worker_id, int $src_worker_id, mixed $data): void
    {
        $this->dispatchEvent(new ServerTask($server, $worker_id, $src_worker_id, $data));
    }

    #[ServerCallback]
    public function onFinish(Server $server, int $worker_id, mixed $data): void
    {
        $this->dispatchEvent(new ServerFinish($server, $worker_id, $data));
    }

    #[ServerCallback]
    public function onPipeMessage(Server $server, int $src_worker_id, mixed $message): void
    {
        $this->dispatchEvent(new ServerPipeMessage($server, $src_worker_id, $message));
    }

    #[ServerCallback]
    public function onWorkerError(Server $server, int $worker_id, int $worker_pid, int $exit_code, int $signal): void
    {
        $this->dispatchEvent(new ServerWorkerError($server, $worker_id, $worker_pid, $exit_code, $signal));
    }

    #[ServerCallback]
    public function onManagerStop(Server $server): void
    {
        $this->dispatchEvent(new ServerManagerStop($server));
    }

    protected function registerServerCallbacks(Server $server): void
    {
        $rClass = new ReflectionClass($this);

        foreach ($rClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getAttributes(ServerCallback::class) !== []) {
                $name = $method->getName();
                $server->on(substr($name, 2), [$this, $name]);
            }
        }
    }

    public function start(): void
    {
        if (MANAPHP_COROUTINE_ENABLED) {
            Runtime::enableCoroutine();
        }

        $this->bootstrap();

        $server = new Server($this->host, $this->port);
        $server->set($this->settings);
        $this->registerServerCallbacks($server);

        echo PHP_EOL, str_repeat('+', 80), PHP_EOL;

        $settings = json_stringify($this->settings);
        console_log('info', ['listen on: %s:%d with setting: %s', $this->host, $this->port, $settings]);
        $this->dispatchEvent(new ServerReady($server));
        $host = $this->host === '0.0.0.0' ? '127.0.0.1' : $this->host;
        $prefix = $this->config->get(RouterInterface::class)['prefix'] ?? '';
        $prefix = ltrim($prefix, '?');
        /** @noinspection HttpUrlsUsage */
        console_log('info', sprintf('http://%s:%s%s', $host, $this->port, $prefix));
        $server->start();
        console_log('info', 'shutdown');
    }

    #[ServerCallback]
    public function onRequest(Request $request, Response $response): void
    {
        $uri = $request->server['request_uri'];
        if ($uri === '/favicon.ico') {
            $response->status(404);
            $response->end();
            return;
        }

        $this->prepareGlobals($request);

        if (!empty($this->settings['enable_static_handler']) && $this->staticHandler->isFile($uri)) {
            if (($file = $this->staticHandler->getFile($uri)) !== null) {
                $response->header('Content-Type', $this->staticHandler->getMimeType($file));
                $response->sendfile($file);
            } else {
                $response->status(404, 'Not Found');
                $response->end('');
            }
        } else {
            $context = $this->getContext();

            $context->response = $response;

            try {
                $this->requestHandler->handle();
            } catch (Throwable $throwable) {
                $str = date('c') . ' ' . $throwable::class . ': ' . $throwable->getMessage() . PHP_EOL;
                $str .= '    at ' . $throwable->getFile() . ':' . $throwable->getLine() . PHP_EOL;
                $str .= preg_replace('/#\d+\s/', '    at ', $throwable->getTraceAsString());
                echo $str . PHP_EOL;
            }
        }

        $this->contextManager->resetContexts();
    }

    public function send(): void
    {
        $context = $this->getContext();

        $response = $context->response;

        $http_code = $this->response->getStatusCode();
        $reason = $this->response->getStatusText($http_code);
        $response->status($http_code, $reason);

        foreach ($this->response->getHeaders() as $name => $value) {
            $response->header($name, $value, false);
        }

        $prefix = $this->router->getPrefix();
        foreach ($this->response->getCookies() as $cookie) {
            $response->cookie(
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
            $response->end('');
        } elseif ($this->request->method() === 'HEAD') {
            $response->header('Content-Length', (string)strlen($content), false);
            $response->end('');
        } elseif ($file = $this->response->getFile()) {
            $response->sendfile($this->alias->resolve($file));
        } else {
            $response->end($content);
        }
    }
}
