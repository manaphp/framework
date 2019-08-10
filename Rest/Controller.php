<?php

namespace ManaPHP\Rest;

use ManaPHP\Component;
use ManaPHP\Logger\LogCategorizable;

/**
 * Class ManaPHP\Rest\Controller
 *
 * @package controller
 *
 * @method bool beforeInvoke(string $action);
 * @method bool afterInvoke(string $action, mixed $r);
 *
 * @property-read \ManaPHP\Security\CaptchaInterface      $captcha
 * @property-read \ManaPHP\Http\RequestInterface          $request
 * @property-read \ManaPHP\Http\ResponseInterface         $response
 * @property-read \ManaPHP\DispatcherInterface            $dispatcher
 * @property-read \ManaPHP\Message\QueueInterface         $messageQueue
 * @property-read \ManaPHP\Db\Model\MetadataInterface     $modelsMetadata
 * @property-read \ManaPHP\Security\HtmlPurifierInterface $htmlPurifier
 * @property-read \ManaPHP\RouterInterface                $router
 */
abstract class Controller extends Component implements LogCategorizable
{
    public function categorizeLog()
    {
        return basename(str_replace('\\', '.', static::class), 'Controller');
    }

    /**
     * @return array
     */
    public function getAcl()
    {
        return [];
    }
}