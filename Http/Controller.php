<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Eventing\EventTrait;
use ManaPHP\Identifying\IdentityInterface;
use ManaPHP\Logging\Logger\LogCategorizable;

class Controller implements LogCategorizable
{
    use EventTrait;

    #[Inject] protected RequestInterface $request;
    #[Inject] protected ResponseInterface $response;
    #[Inject] protected CookiesInterface $cookies;
    #[Inject] protected RouterInterface $router;
    #[Inject] protected DispatcherInterface $dispatcher;
    #[Inject] protected IdentityInterface $identity;

    public function categorizeLog(): string
    {
        return basename(str_replace('\\', '.', static::class), 'Controller');
    }

    /**
     * @return array =[$field=>[60,'burst'=>3],'*'=>'','index'=>'','list'=>'','detail'=>'','captcha'=>'',
     *               'create'=>'','update'=>'','edit'=>'', 'save'=>'','delete'=>'']
     */
    public function getRateLimit(): array
    {
        return [];
    }

    /**
     * @return array =[$field => ["etag", "max-age"=>1, "Cache-Control"=>"private, max-age=0, no-store, no-cache,
     *               must-revalidate"]]
     */
    public function getHttpCache(): array
    {
        return [];
    }

    /**
     * @return array =['*'=>'', 'index'=>'','list'=>'','detail'=>'','captcha'=>'',
     *               'create'=>'','update'=>'','edit'=>'', 'save'=>'','delete'=>'']
     */
    public function getPageCache(): array
    {
        return [];
    }
}
