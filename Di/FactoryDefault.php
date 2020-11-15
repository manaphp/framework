<?php

namespace ManaPHP\Di;

use ManaPHP\Di;

/**
 * Class ManaPHP\Di\FactoryDefault
 *
 * @package di
 */
class FactoryDefault extends Di
{
    /**
     * \ManaPHP\Di\FactoryDefault constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->_definitions = [
            'eventsManager'    => 'ManaPHP\Event\Manager',
            'poolManager'      => 'ManaPHP\Pool\Manager',
            'alias'            => 'ManaPHP\Alias',
            'aopCutter'        => 'ManaPHP\Aop\Cutter',
            'dotenv'           => 'ManaPHP\Dotenv',
            'configure'        => 'ManaPHP\Configuration\Configure',
            'settings'         => 'ManaPHP\Configuration\Settings\Adapter\Redis',
            'invoker'          => 'ManaPHP\Invoker',
            'modelsMetadata'   => 'ManaPHP\Db\Model\Metadata\Adapter\Memory',
            'validator'        => 'ManaPHP\Validator',
            'crypt'            => 'ManaPHP\Security\Crypt',
            'logger'           => 'ManaPHP\Logging\Logger\Adapter\File',
            'renderer'         => 'ManaPHP\Renderer',
            'assetBundle'      => 'ManaPHP\Renderer\AssetBundle',
            'cache'            => 'ManaPHP\Caching\Cache\Adapter\Redis',
            'ipcCache'         => 'ManaPHP\Ipc\Cache\Adapter\Apcu',
            'httpClient'       => ['class' => 'ManaPHP\Http\Client', 'engine' => 'ManaPHP\Http\Client\Engine\Curl'],
            'restClient'       => ['class' => 'ManaPHP\Http\Client', 'engine' => 'ManaPHP\Http\Client\Engine\Stream'],
            'paginator'        => 'ManaPHP\Paginator',
            'random'           => 'ManaPHP\Security\Random',
            'messageQueue'     => 'ManaPHP\Messaging\Queue\Adapter\Redis',
            'swordCompiler'    => 'ManaPHP\Renderer\Engine\Sword\Compiler',
            'htmlPurifier'     => 'ManaPHP\Security\HtmlPurifier',
            'db'               => 'ManaPHP\Db',
            'redis'            => 'ManaPHP\Redis',
            'redisCache'       => '@redis',
            'redisDb'          => '@redis',
            'redisBroker'      => '@redis',
            'mongodb'          => 'ManaPHP\Mongodb',
            'translator'       => 'ManaPHP\I18n\Translator',
            'rabbitmq'         => 'ManaPHP\Amqp',
            'relationsManager' => 'ManaPHP\Model\Relation\Manager',
            'mailer'           => 'ManaPHP\Mailing\Mailer\Adapter\Smtp',
            'aclBuilder'       => 'ManaPHP\Http\Authorization\AclBuilder',
            'bosClient'        => 'ManaPHP\Bos\Client',
            'wsPusher'         => 'ManaPHP\Ws\Pusher',
            'identity'         => 'ManaPHP\Identity',
            'coroutineManager' => 'ManaPHP\Coroutine\Manager',
            'jwt'              => 'ManaPHP\Token\Jwt',
            'scopedJwt'        => 'ManaPHP\Token\ScopedJwt',
            'wsClient'         => 'ManaPHP\Ws\Client',
            'pubSub'           => 'ManaPHP\Messaging\PubSub\Adapter\Redis',
            'dataDump'         => 'ManaPHP\DataDump',
        ];
    }
}
