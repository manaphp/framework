<?php

declare(strict_types=1);

namespace ManaPHP\Ws\Chatting;

use ManaPHP\Coroutine;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Identifying\IdentityInterface;
use ManaPHP\Messaging\PubSubInterface;
use ManaPHP\Ws\Chatting\Server\Event\ServerPushed;
use ManaPHP\Ws\Chatting\Server\Event\ServerPushing;
use ManaPHP\Ws\Chatting\Server\Event\UserCome;
use ManaPHP\Ws\Chatting\Server\Event\UserLeave;
use ManaPHP\Ws\ServerInterface as WsServerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use function count;
use function strlen;

class Server implements ServerInterface
{
    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected LoggerInterface $logger;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected IdentityInterface $identity;
    #[Autowired] protected WsServerInterface $wsServer;
    #[Autowired] protected PubSubInterface $pubSub;

    #[Autowired] protected string $prefix = 'ws_chatting:';
    #[Autowired] protected bool $dedicated = false;

    protected array $fds = [];
    protected array $ids;
    protected array $names;

    public function open(int $fd, string $room): void
    {
        if (!$this->dedicated) {
            $this->fds[$fd] = true;
        }

        if (($id = $this->identity->isGuest() ? 0 : $this->identity->getId()) !== 0) {
            $this->ids[$room][$id][$fd] = true;
        }

        if (($name = $this->identity->isGuest() ? '' : $this->identity->getName()) !== '') {
            $this->names[$room][$name][$fd] = true;
        }

        $this->eventDispatcher->dispatch(new UserCome($this, $fd, $id, $name, $room));
    }

    public function close(int $fd, string $room): void
    {
        if (!$this->dedicated) {
            unset($this->fds[$fd]);
        }

        if (($id = $this->identity->isGuest() ? 0 : $this->identity->getId()) !== 0) {
            unset($this->ids[$room][$id][$fd]);
            if (count($this->ids[$room][$id]) === 0) {
                unset($this->ids[$room][$id]);
            }
        }

        if (($name = $this->identity->isGuest() ? '' : $this->identity->getName()) !== '') {
            unset($this->names[$room][$name][$fd]);
            if (count($this->names[$room][$name]) === 0) {
                unset($this->names[$room][$name]);
            }
        }

        $this->eventDispatcher->dispatch(new UserLeave($this, $id, $id, $name, $room));
    }

    public function push(int $fd, string $message): void
    {
        $this->wsServer->push($fd, $message);
    }

    public function pushToRoom(string $room, string $message): void
    {
        $sent = [];

        foreach ($this->ids[$room] ?? [] as $fds) {
            foreach ($fds as $fd => $_) {
                $sent[$fd] = true;
                $this->push($fd, $message);
            }
        }

        foreach ($this->names[$room] ?? [] as $fds) {
            foreach ($fds as $fd => $_) {
                if (!isset($sent[$fd])) {
                    $sent[$fd] = true;
                    $this->push($fd, $message);
                }
            }
        }
    }

    public function pushToId(string $room, array $receivers, string $message): void
    {
        foreach ($receivers as $id) {
            foreach ($this->ids[$room][$id] ?? [] as $fd => $_) {
                $this->push($fd, $message);
            }
        }
    }

    public function pushToName(string $room, array $receivers, string $message): void
    {
        foreach ($receivers as $name) {
            foreach ($this->names[$room][$name] ?? [] as $fd => $_) {
                $this->push($fd, $message);
            }
        }
    }

    public function broadcast(string $message): void
    {
        if ($this->dedicated) {
            $this->wsServer->broadcast($message);
        } else {
            foreach ($this->fds as $fd => $_) {
                $this->push($fd, $message);
            }
        }
    }

    public function closeRoom(string $room, string $message): void
    {
        $sent = [];
        foreach ($this->ids[$room] ?? [] as $fds) {
            foreach ($fds as $fd => $_) {
                $sent[$fd] = true;
                $this->push($fd, $message);
            }
        }
        unset($this->ids[$room]);

        foreach ($this->names[$room] ?? [] as $fds) {
            foreach ($fds as $fd => $_) {
                if (!isset($sent[$fd])) {
                    $sent[$fd] = true;
                    $this->push($fd, $message);
                }
            }
        }
        unset($this->names[$room]);
    }

    public function kickoutId(string $room, array $receivers, string $message): void
    {
        $sent = [];

        foreach ($receivers as $id) {
            foreach ($this->ids[$room][$id] ?? [] as $fds) {
                foreach ($fds as $fd => $_) {
                    $sent[$fd] = true;
                    $this->push($fd, $message);
                }
            }
            unset($this->ids[$room][$id]);
        }

        foreach ($this->names[$room] ?? [] as $name => $fds) {
            foreach ($fds as $fd => $_) {
                if (isset($sent[$fd])) {
                    unset($this->names[$room][$name]);
                }
            }
        }
    }

    public function kickoutName(string $room, array $receivers, string $message): void
    {
        $sent = [];

        foreach ($receivers as $name) {
            foreach ($this->names[$room][$name] ?? [] as $fds) {
                foreach ($fds as $fd => $_) {
                    $sent[$fd] = true;
                    $this->push($fd, $message);
                }
            }
            unset($this->names[$room][$name]);
        }

        foreach ($this->ids[$room] ?? [] as $id => $fds) {
            foreach ($fds as $fd => $_) {
                if (isset($sent[$fd])) {
                    unset($this->ids[$room][$id]);
                }
            }
        }
    }

    public function dispatch(string $type, string $room, array $receivers, string $message): void
    {
        if ($type === 'message.room') {
            $this->pushToRoom($room, $message);
        } elseif ($type === 'message.broadcast') {
            $this->broadcast($message);
        } elseif ($type === 'message.id') {
            $this->pushToId($room, $receivers, $message);
        } elseif ($type === 'message.name') {
            $this->pushToName($room, $receivers, $message);
        } elseif ($type === 'room.close') {
            $this->closeRoom($room, $message);
        } elseif ($type === 'kickout.id') {
            $this->kickoutId($room, $receivers, $message);
        } elseif ($type === 'kickout.name') {
            $this->kickoutName($room, $receivers, $message);
        } else {
            $this->logger->warning(
                'unknown `{0}` type message: {1}',
                [$type, $message, 'category' => 'chatServer.bad_type']
            );
        }
    }

    public function start(): void
    {
        Coroutine::create(
            function () {
                $this->pubSub->psubscribe(
                    [$this->prefix . '*'],
                    function ($channel, $message) {
                        list($type, $room, $receivers) = explode(':', substr($channel, strlen($this->prefix)), 4);
                        if ($type !== null && $room !== null && $receivers !== null) {
                            $receivers = explode(',', $receivers);
                            $this->eventDispatcher->dispatch(new ServerPushing($this, $type, $receivers, $message));
                            $this->dispatch($type, $room, $receivers, $message);
                            $this->eventDispatcher->dispatch(new ServerPushed($this, $type, $receivers, $message));
                        } else {
                            $this->logger->warning($channel, ['category' => 'chatServer.bad_channel']);
                        }
                    }
                );
            }
        );
    }
}
