<?php

declare(strict_types=1);

namespace ManaPHP\Http\Server\Listeners;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Di\ConfigInterface;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Helper\SuppressWarnings;
use ManaPHP\Http\RouterInterface;
use ManaPHP\Http\Server\Event\ServerReady;
use ManaPHP\Http\Server\Event\ServerShutdown;
use function console_log;
use function json_stringify;
use function ltrim;
use function sprintf;

class LogServerStatusListener
{
    #[Autowired] protected ConfigInterface $config;

    #[Config] protected string $app_id;

    public function onServerReady(#[Event] ServerReady $event): void
    {
        $host = $event->host;
        $port = $event->port;
        $settings = $event->settings;

        $settings = json_stringify($settings);

        console_log('info', ['listen on: %s:%d with setting: %s', $host, $port, $settings]);

        $prefix = $this->config->get(RouterInterface::class)['prefix'] ?? '';
        $prefix = ltrim($prefix, '?');
        $host = $host === '0.0.0.0' ? '127.0.0.1' : $host;
        /** @noinspection HttpUrlsUsage */
        console_log('info', sprintf('http://%s:%s%s', $host, $port, $prefix));
    }

    public function onServerShutdown(#[Event] ServerShutdown $event): void
    {
        SuppressWarnings::unused($event);
        console_log('info', 'server shutdown');
    }
}
