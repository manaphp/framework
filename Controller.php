<?php
namespace ManaPHP;

use ManaPHP\Logger\LogCategorizable;

/**
 * Class Controller
 * @package ManaPHP
 *
 * @property-read \ManaPHP\InvokerInterface $invoker
 */
class Controller extends Component implements LogCategorizable
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

    /**
     * @param string $action
     *
     * @return mixed
     */
    public function invoke($action)
    {
        return $this->invoker->invoke($this, $action . 'Action');
    }
}