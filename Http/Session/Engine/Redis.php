<?php
namespace ManaPHP\Http\Session\Engine;

use ManaPHP\Component;
use ManaPHP\Http\Session\EngineInterface;

/**
 * Class ManaPHP\Http\Session\Engine\Redis
 *
 * @package session\engine
 *
 * @property \Redis $sessionRedis
 */
class Redis extends Component implements EngineInterface
{
    /**
     * @var string
     */
    protected $_prefix;

    /**
     * Redis constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (is_object($options)) {
            $options = (array)$options;
        }

        if (isset($options['prefix'])) {
            $this->_prefix = $options['prefix'];
        }
    }

    /**
     * @param \ManaPHP\DiInterface $dependencyInjector
     *
     * @return static
     */
    public function setDependencyInjector($dependencyInjector)
    {
        parent::setDependencyInjector($dependencyInjector);

        $this->_dependencyInjector->setAliases('redis', 'sessionRedis');

        if ($this->_prefix === null) {
            $this->_prefix = $this->_dependencyInjector->configure->appID . ':session:';
        }

        return $this;
    }

    /**
     * @param string $prefix
     *
     * @return static
     */
    public function setPrefix($prefix)
    {
        $this->_prefix = $prefix;

        return $this;
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
    public function read($sessionId)
    {
        $data = $this->sessionRedis->get($this->_prefix . $sessionId);
        return is_string($data) ? $data : '';
    }

    /**
     * @param string $sessionId
     * @param string $data
     * @param int    $ttl
     *
     * @return bool
     */
    public function write($sessionId, $data, $ttl)
    {
        return $this->sessionRedis->set($this->_prefix . $sessionId, $data, $ttl);
    }

    /**
     * @param string $sessionId
     *
     * @return bool
     */
    public function destroy($sessionId)
    {
        $this->sessionRedis->delete($this->_prefix . $sessionId);

        return true;
    }

    /**
     * @param int $ttl
     *
     * @return bool
     */
    public function gc($ttl)
    {
        $this->clean();

        return true;
    }

    /**
     * @return void
     */
    public function clean()
    {

    }
}