<?php

namespace ManaPHP\Logger\Adapter;

use ManaPHP\Logger;

/**
 * Class ManaPHP\Logger\Adapter\File
 *
 * @package logger
 */
class File extends Logger
{
    /**
     * @var string
     */
    protected $_file = '@data/logger/app.log';

    /**
     * @var string
     */
    protected $_format = '[:date][:client_ip][:request_id16][:level][:category][:location] :message';

    /**
     * @var string
     */
    protected $_tail = PHP_EOL;

    /**
     * \ManaPHP\Logger\Adapter\File constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        if (isset($options['file'])) {
            $this->_file = $options['file'];
        }

        if (isset($options['format'])) {
            $this->_format = $options['format'];
        }

        if (isset($options['tail'])) {
            $this->_tail = $options['tail'];
        }

        $this->attachEvent('request:end', [$this, 'onRequestEnd']);
    }

    public function onRequestEnd()
    {
        if ($this->_tail !== '') {
            $this->_write($this->_tail);
        }
    }

    /**
     * @param \ManaPHP\Logger\Log $log
     *
     * @return string
     */
    protected function _format($log)
    {
        $replaced = [];

        $replaced[':date'] = date('Y-m-d\TH:i:s', $log->timestamp) . sprintf('.%03d', ($log->timestamp - (int)$log->timestamp) * 1000);
        $replaced[':client_ip'] = $log->client_ip ?: '-';
        $replaced[':request_id'] = $log->request_id ?: '-';
        $replaced[':request_id16'] = $log->request_id ? substr($log->request_id, 0, 16) : '-';
        $replaced[':category'] = $log->category;
        $replaced[':location'] = "$log->file:$log->line";
        $replaced[':level'] = strtoupper($log->level);
        if ($log->category === 'exception') {
            $replaced[':message'] = '';
            /** @noinspection SuspiciousAssignmentsInspection */
            $replaced[':message'] = preg_replace('#[\\r\\n]+#', '\0' . strtr($this->_format, $replaced), $log->message) . PHP_EOL;
        } else {
            $replaced[':message'] = $log->message . PHP_EOL;
        }

        return strtr($this->_format, $replaced);
    }

    /**
     * @param string $str
     *
     * @return void
     */
    protected function _write($str)
    {
        $file = $this->alias->resolve($this->_file);
        if (!is_file($file)) {
            $dir = dirname($file);
            /** @noinspection NotOptimalIfConditionsInspection */
            if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
                /** @noinspection ForgottenDebugOutputInspection */
                trigger_error("Unable to create $dir directory: " . error_get_last()['message'], E_USER_WARNING);
            }
        }

        //LOCK_EX flag fight with SWOOLE COROUTINE
        if (file_put_contents($file, $str, FILE_APPEND) === false) {
            /** @noinspection ForgottenDebugOutputInspection */
            trigger_error('Write log to file failed: ' . $file, E_USER_WARNING);
        }
    }

    /**
     * @param \ManaPHP\Logger\Log[] $logs
     *
     * @return void
     */
    public function append($logs)
    {
        foreach ($logs as $log) {
            $this->_write($this->_format($log));
        }
    }
}