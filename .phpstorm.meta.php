<?php
// This file is not a CODE, it makes no sense and won't run or validate
// Its AST serves IDE as DATA source to make advanced type inference decisions.

namespace PHPSTORM_META {
    $STATIC_METHOD_TYPES = [
        \ManaPHP\DiInterface::getShared('') => [
            'eventsManager' instanceof \ManaPHP\Event\ManagerInterface,
            'alias' instanceof \ManaPHP\AliasInterface,
            'dotenv' instanceof \ManaPHP\DotenvInterface,
            'configure' instanceof \ManaPHP\Configuration\Configure,
            'settings' instanceof \ManaPHP\Configuration\SettingsInterface,
            'errorHandler' instanceof \ManaPHP\ErrorHandlerInterface,
            'router' instanceof \ManaPHP\RouterInterface,
            'dispatcher' instanceof \ManaPHP\Mvc\Dispatcher,
            'actionInvoker' instanceof \ManaPHP\ActionInvokerInterface,
            'url' instanceof \ManaPHP\View\UrlInterface,
            'modelsManager' instanceof \ManaPHP\Mvc\Model\ManagerInterface,
            'modelsMetadata' instanceof \ManaPHP\Mvc\Model\MetadataInterface,
            'modelsValidator' instanceof \ManaPHP\Model\ValidatorInterface,
            'response' instanceof \ManaPHP\Http\ResponseInterface,
            'cookies' instanceof \ManaPHP\Http\CookiesInterface,
            'request' instanceof \ManaPHP\Http\RequestInterface,
            'filter' instanceof \ManaPHP\Http\FilterInterface,
            'crypt' instanceof \ManaPHP\Security\CryptInterface,
            'flash' instanceof \ManaPHP\View\FlashInterface,
            'flashSession' instanceof \ManaPHP\View\FlashInterface,
            'session' instanceof \ManaPHP\Http\SessionInterface,
            'view' instanceof \ManaPHP\ViewInterface,
            'logger' instanceof \ManaPHP\LoggerInterface,
            'renderer' instanceof \ManaPHP\RendererInterface,
            'debugger' instanceof \ManaPHP\DebuggerInterface,
            'password' instanceof \ManaPHP\Authentication\PasswordInterface,
            'serializer' instanceof \ManaPHP\Serializer,
            'cache' instanceof \ManaPHP\CacheInterface,
            'counter' instanceof \ManaPHP\CounterInterface,
            'httpClient' instanceof \ManaPHP\Curl\EasyInterface,
            'captcha' instanceof \ManaPHP\Security\CaptchaInterface,
            'csrfToken' instanceof \ManaPHP\Security\CsrfTokenInterface,
            'authorization' instanceof \ManaPHP\Security\AuthorizationInterface,
            'identity' instanceof \ManaPHP\Security\IdentityInterface,
            'paginator' instanceof \ManaPHP\Paginator,
            'filesystem' instanceof \ManaPHP\Filesystem\Adapter\File,
            'random' instanceof \ManaPHP\Security\RandomInterface,
            'messageQueue' instanceof \ManaPHP\Message\QueueInterface,
            'crossword' instanceof \ManaPHP\Text\CrosswordInterface,
            'rateLimiter' instanceof \ManaPHP\Security\RateLimiterInterface,
            'linearMeter' instanceof \ManaPHP\Meter\LinearInterface,
            'roundMeter' instanceof \ManaPHP\Meter\RoundInterface,
            'secint' instanceof \ManaPHP\Security\SecintInterface,
            'swordCompiler' instanceof \ManaPHP\Renderer\Engine\Sword\Compiler,
            'stopwatch' instanceof \ManaPHP\StopwatchInterface,
            'tasksManager' instanceof \ManaPHP\Task\ManagerInterface,
            'viewsCache' instanceof \ManaPHP\Cache\EngineInterface,
            'modelsCache' instanceof \ManaPHP\Cache\EngineInterface,
            'htmlPurifier' instanceof \ManaPHP\Security\HtmlPurifierInterface,
            'netConnectivity' instanceof \ManaPHP\Net\ConnectivityInterface,
            'db' instanceof \ManaPHP\DbInterface,
            'redis' instanceof \ManaPHP\Redis,
            'mongodb' instanceof \ManaPHP\MongodbInterface,
            'translation' instanceof \ManaPHP\I18n\TranslationInterface,
            'rabbitmq' instanceof \ManaPHP\AmqpInterface,
            'relationsManager' instanceof \ManaPHP\Model\Relation\Manager,
            'di' instanceof \ManaPHP\Di | \ManaPHP\DiInterface,
            'application' instanceof \ManaPHP\ApplicationInterface,
            'jwt' instanceof \ManaPHP\Authentication\Token\Adapter\Jwt,
            'mwt' instanceof \ManaPHP\Authentication\Token\Adapter\Mwt,
            'mailer' instanceof \ManaPHP\MailerInterface,
        ],
        \di('') => [
            'di' instanceof \ManaPHP\DiInterface,
            'eventsManager' instanceof \ManaPHP\Event\ManagerInterface,
            'alias' instanceof \ManaPHP\AliasInterface,
            'dotenv' instanceof \ManaPHP\DotenvInterface,
            'configure' instanceof \ManaPHP\Configuration\Configure,
            'settings' instanceof \ManaPHP\Configuration\SettingsInterface,
            'errorHandler' instanceof \ManaPHP\ErrorHandlerInterface,
            'router' instanceof \ManaPHP\RouterInterface,
            'dispatcher' instanceof \ManaPHP\Mvc\Dispatcher,
            'actionInvoker' instanceof \ManaPHP\ActionInvokerInterface,
            'url' instanceof \ManaPHP\View\UrlInterface,
            'modelsManager' instanceof \ManaPHP\Mvc\Model\ManagerInterface,
            'modelsMetadata' instanceof \ManaPHP\Mvc\Model\MetadataInterface,
            'modelsValidator' instanceof \ManaPHP\Model\ValidatorInterface,
            'response' instanceof \ManaPHP\Http\ResponseInterface,
            'cookies' instanceof \ManaPHP\Http\CookiesInterface,
            'request' instanceof \ManaPHP\Http\RequestInterface,
            'filter' instanceof \ManaPHP\Http\FilterInterface,
            'crypt' instanceof \ManaPHP\Security\CryptInterface,
            'flash' instanceof \ManaPHP\View\FlashInterface,
            'flashSession' instanceof \ManaPHP\View\FlashInterface,
            'session' instanceof \ManaPHP\Http\SessionInterface,
            'view' instanceof \ManaPHP\ViewInterface,
            'logger' instanceof \ManaPHP\LoggerInterface,
            'renderer' instanceof \ManaPHP\RendererInterface,
            'debugger' instanceof \ManaPHP\DebuggerInterface,
            'password' instanceof \ManaPHP\Authentication\PasswordInterface,
            'serializer' instanceof \ManaPHP\Serializer,
            'cache' instanceof \ManaPHP\CacheInterface,
            'counter' instanceof \ManaPHP\CounterInterface,
            'httpClient' instanceof \ManaPHP\Curl\EasyInterface,
            'captcha' instanceof \ManaPHP\Security\CaptchaInterface,
            'csrfToken' instanceof \ManaPHP\Security\CsrfTokenInterface,
            'authorization' instanceof \ManaPHP\Security\AuthorizationInterface,
            'identity' instanceof \ManaPHP\Security\IdentityInterface,
            'paginator' instanceof \ManaPHP\Paginator,
            'filesystem' instanceof \ManaPHP\Filesystem\Adapter\File,
            'random' instanceof \ManaPHP\Security\RandomInterface,
            'messageQueue' instanceof \ManaPHP\Message\QueueInterface,
            'crossword' instanceof \ManaPHP\Text\CrosswordInterface,
            'rateLimiter' instanceof \ManaPHP\Security\RateLimiterInterface,
            'linearMeter' instanceof \ManaPHP\Meter\LinearInterface,
            'roundMeter' instanceof \ManaPHP\Meter\RoundInterface,
            'secint' instanceof \ManaPHP\Security\SecintInterface,
            'swordCompiler' instanceof \ManaPHP\Renderer\Engine\Sword\Compiler,
            'stopwatch' instanceof \ManaPHP\StopwatchInterface,
            'tasksManager' instanceof \ManaPHP\Task\ManagerInterface,
            'viewsCache' instanceof \ManaPHP\Cache\EngineInterface,
            'modelsCache' instanceof \ManaPHP\Cache\EngineInterface,
            'htmlPurifier' instanceof \ManaPHP\Security\HtmlPurifierInterface,
            'netConnectivity' instanceof \ManaPHP\Net\ConnectivityInterface,
            'db' instanceof \ManaPHP\DbInterface,
            'redis' instanceof \ManaPHP\Redis,
            'mongodb' instanceof \ManaPHP\MongodbInterface,
            'translation' instanceof \ManaPHP\I18n\TranslationInterface,
            'rabbitmq' instanceof \ManaPHP\AmqpInterface,
            'relationsManager' instanceof \ManaPHP\Model\Relation\Manager,
            'di' instanceof \ManaPHP\Di | \ManaPHP\DiInterface,
            'application' instanceof \ManaPHP\ApplicationInterface,
            'authenticationToken' instanceof \ManaPHP\Authentication\Token\Adapter\Mwt,
            'jwt' instanceof \ManaPHP\Authentication\Token\Adapter\Jwt,
            'mwt' instanceof \ManaPHP\Authentication\Token\Adapter\Mwt,
            'mailer' instanceof \ManaPHP\MailerInterface,
        ],
        \ManaPHP\DiInterface::get('') => [
            '' == '@',
        ],
        \ManaPHP\DiInterface::getInstance('') => [
            '' == '@',
        ]
    ];
}

/**
 * @xglobal $view ManaPHP\ViewInterface
 */
/**
 * @var \ManaPHP\ViewInterface         $view
 * @var \ManaPHP\Di                    $di
 * @var \ManaPHP\Http\RequestInterface $request
 * @var \ManaPHP\RendererInterface     $renderer
 */
$view = null;
$di = null;
$request = null;
unset($view, $renderer);

class_exists('\Elasticsearch\Client') || class_alias('\stdClass', '\Elasticsearch\Client');