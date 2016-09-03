<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/19
 * Time: 17:20
 */
namespace ManaPHP\Http\Session\Adapter;

use ManaPHP\Component;
use ManaPHP\Http\Session\Adapter\Exception as SessionException;
use ManaPHP\Http\Session\AdapterInterface;

class File extends Component implements AdapterInterface
{
    /**
     * @var int
     */
    protected $_ttl;

    /**
     * @var string
     */
    protected $_dir = '@data/session';

    /**
     * @var string
     */
    protected $_extension = '.session';

    /**
     * @var int
     */
    protected $_level = 1;

    /**
     * File constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (is_object($options)) {
            $options = (array)$options;
        }

        $this->_ttl = (int)(isset($options['ttl']) ? $options['ttl'] : ini_get('session.gc_maxlifetime'));

        if (isset($options['dir'])) {
            $this->_dir = ltrim($options['dir'], '\\/');
        }

        if (isset($options['extension'])) {
            $this->_extension = $options['extension'];
        }

        if (isset($options['level'])) {
            $this->_level = $options['level'];
        }
    }

    /**
     * @param string $savePath
     * @param string $sessionName
     *
     * @return bool
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * @return bool
     */
    public function close()
    {
        return true;
    }

    /**
     * @param string $sessionId
     *
     * @return string
     */
    protected function _getFileName($sessionId)
    {
        $shard = '';

        for ($i = 0; $i < $this->_level; $i++) {
            $shard .= '/' . substr($sessionId, $i + $i, 2);
        }

        return $this->alias->resolve($this->_dir . $shard . '/' . $sessionId . $this->_extension);
    }

    /**
     * @param string $sessionId
     *
     * @return string
     */
    public function read($sessionId)
    {
        $file = $this->_getFileName($sessionId);

        if (file_exists($file) && filemtime($file) >= time()) {
            return file_get_contents($file);
        } else {
            return '';
        }
    }

    /**
     * @param string $sessionId
     * @param string $data
     *
     * @throws \ManaPHP\Http\Session\Exception
     */
    public function write($sessionId, $data)
    {
        $file = $this->_getFileName($sessionId);
        $dir = dirname($file);
        if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new SessionException('create `:dir` session directory failed: :message'/**m0842502d4c2904242*/,
                ['dir' => $dir, 'message' => SessionException::getLastErrorMessage()]);
        }

        if (file_put_contents($file, $data, LOCK_EX) === false) {
            trigger_error(strtr('write `:file` session file failed: :message'/**m0f7ee56f71e1ec344*/, [':file' => $file, ':message' => SessionException::getLastErrorMessage()]));
        }

        file_put_contents($file, $data);

        @touch($file, time() + $this->_ttl);
        clearstatcache(true, $file);
    }

    /**
     * @param string $sessionId
     *
     * @return bool
     */
    public function destroy($sessionId)
    {
        $file = $this->_getFileName($sessionId);

        if (file_exists($file)) {
            @unlink($file);
        }

        return true;
    }

    /**
     * @param int $ttl
     *
     * @return bool
     */
    public function gc($ttl)
    {
        return true;
    }
}