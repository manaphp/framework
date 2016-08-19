<?php
namespace ManaPHP\Log\Adapter;

use ManaPHP\Log\AdapterInterface;

class Memory implements AdapterInterface
{
    /**
     * @var array
     */
    protected $_logs = [];

    /**
     * @param string $level
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function log($level, $message, $context = [])
    {
        $this->_logs[] = ['level' => $level, 'message' => $message, 'context' => $context];
    }

    /**
     * @return array
     */
    public function getLogs()
    {
        return $this->_logs;
    }
}