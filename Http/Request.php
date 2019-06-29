<?php

namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\MissingFieldException;
use ManaPHP\Http\Request\File;
use ManaPHP\Http\Request\File\Exception as FileException;

class RequestContext
{
    public $_GET = [];
    public $_POST = [];
    public $_REQUEST = [];
    public $_SERVER = [];
    public $_COOKIE = [];
    public $_FILES = [];
}

/**
 * Class ManaPHP\Http\Request
 *
 * @package request
 *
 * @property-read \ManaPHP\DispatcherInterface $dispatcher
 * @property \ManaPHP\Http\RequestContext      $_context
 */
class Request extends Component implements RequestInterface
{
    /**
     * @return \ManaPHP\Http\RequestContext
     */
    public function getGlobals()
    {
        return $this->_context;
    }

    /**
     * Gets a cookie
     *
     * @param string $name
     * @param string $default
     *
     * @return string|array
     */
    public function getCookie($name = null, $default = '')
    {
        $context = $this->_context;

        if ($name === null) {
            return $context->_COOKIE;
        } elseif (isset($context->_COOKIE[$name])) {
            return $context->_COOKIE[$name];
        } else {
            return $default;
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasCookie($name)
    {
        $context = $this->_context;

        return isset($context->_COOKIE[$name]);
    }

    /**
     * @param string $field
     * @param mixed  $value
     * @param mixed  $default
     *
     * @return mixed
     */
    protected function _normalizeValue($field, $value, $default)
    {
        $type = gettype($default);

        if ($type === 'string') {
            return (string)$value;
        } elseif ($type === 'integer') {
            return $this->validator->validateValue($field, $value, 'int');
        } elseif ($type === 'double') {
            return $this->validator->validateValue($field, $value, 'float');
        } elseif ($type === 'boolean') {
            return (bool)$this->validator->validateValue($field, $value, 'bool');
        } else {
            return $value;
        }
    }

    /**
     * Gets a variable from the $_REQUEST
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($name = null, $default = null)
    {
        $source = $this->_context->_REQUEST;

        if ($name === null) {
            return $source;
        }

        if (isset($source[$name]) && $source[$name] !== '') {
            $value = $source[$name];

            if (is_array($value) && is_scalar($default)) {
                throw new InvalidValueException(['the value of `:name` name is not scalar', 'name' => $name]);
            }

            return $default === null ? $value : $this->_normalizeValue($name, $value, $default);
        } elseif ($default === null) {
            return $this->validator->validateValue($name, null, ['required']);
        } else {
            return $default;
        }
    }

    /**
     * @param string $name
     *
     * @return int|string
     */
    public function getId($name = 'id')
    {
        $source = $this->_context->_REQUEST;

        if (isset($source[$name])) {
            $id = $source[$name];
        } else {
            $params = $this->dispatcher->getParams();
            if (isset($params[$name])) {
                $id = $params[$name];
            } elseif (count($params) === 1 && isset($params[0])) {
                $id = $params[0];
            } elseif (isset($source['id'])) {
                return $source['id'];
            } else {
                throw new MissingFieldException(['missing `:id` key value', 'id' => $name]);
            }
        }

        if (!is_scalar($id)) {
            throw new InvalidValueException('primary key value is not scalar');
        }

        return $id;
    }

    /**
     * Gets variable from $_SERVER
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getServer($name = null, $default = '')
    {
        $context = $this->_context;

        if ($name === null) {
            return $context->_SERVER;
        } else {
            return isset($context->_SERVER[$name]) ? $context->_SERVER[$name] : $default;
        }
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->_context->_SERVER['REQUEST_METHOD'];
    }

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getInput($name = null, $default = null)
    {
        $params = $this->dispatcher->getParams();
        if (isset($params[0]) && count($params) === 1) {
            $params = ['id' => $params[0]];
        }

        if ($name === null) {
            return array_merge($this->get(), $params);
        } elseif (isset($params[$name])) {
            return $default === null ? $params[$name] : $this->_normalizeValue($name, $params[$name], $default);
        } else {
            return $this->get($name, $default);
        }
    }

    /**
     * Checks whether $_REQUEST has certain index
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        $context = $this->_context;

        return isset($context->_REQUEST[$name]);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasInput($name)
    {
        return $this->has($name) || $this->dispatcher->hasParam($name);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasServer($name)
    {
        $context = $this->_context;

        return isset($context->_SERVER[$name]);
    }

    /**
     * Gets HTTP schema (http/https)
     *
     * @return string
     */
    public function getScheme()
    {
        if ($scheme = $this->getServer('REQUEST_SCHEME')) {
            return $scheme;
        } else {
            return $this->getServer('HTTPS') === 'on' ? 'https' : 'http';
        }
    }

    /**
     * Checks whether request has been made using ajax
     *
     * @return bool
     */
    public function isAjax()
    {
        return $this->getServer('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest';
    }

    /**
     * @return bool
     */
    public function isWebSocket()
    {
        return $this->getServer('HTTP_UPGRADE') === 'websocket';
    }

    /**
     * @return string
     */
    public function getClientIp()
    {
        return $this->getServer('HTTP_X_REAL_IP') ?: $this->getServer('REMOTE_ADDR');
    }

    /**
     * Gets HTTP user agent used to made the request
     *
     * @return string
     */
    public function getUserAgent()
    {
        return strip_tags($this->getServer('HTTP_USER_AGENT'));
    }

    /**
     * Checks whether HTTP method is POST.
     *
     * @return bool
     */
    public function isPost()
    {
        return $this->getServer('REQUEST_METHOD') === 'POST';
    }

    /**
     * Checks whether HTTP method is GET.
     *
     * @return bool
     */
    public function isGet()
    {
        return $this->getServer('REQUEST_METHOD') === 'GET';
    }

    /**
     * Checks whether HTTP method is PUT.
     *
     * @return bool
     */
    public function isPut()
    {
        return $this->getServer('REQUEST_METHOD') === 'PUT';
    }

    /**
     * Checks whether HTTP method is PATCH.
     *
     * @return bool
     */
    public function isPatch()
    {
        return $this->getServer('REQUEST_METHOD') === 'PATCH';
    }

    /**
     * Checks whether HTTP method is HEAD.
     *
     * @return bool
     */
    public function isHead()
    {
        return $this->getServer('REQUEST_METHOD') === 'HEAD';
    }

    /**
     * Checks whether HTTP method is DELETE.
     *
     * @return bool
     */
    public function isDelete()
    {
        return $this->getServer('REQUEST_METHOD') === 'DELETE';
    }

    /**
     * Checks whether HTTP method is OPTIONS.
     *
     * @return bool
     */
    public function isOptions()
    {
        return $this->getServer('REQUEST_METHOD') === 'OPTIONS';
    }

    /**
     * Checks whether request includes attached files
     * http://php.net/manual/en/features.file-upload.multiple.php
     *
     * @param bool $onlySuccessful
     *
     * @return bool
     */
    public function hasFiles($onlySuccessful = true)
    {
        $context = $this->_context;

        /** @var $_FILES array */
        foreach ($context->_FILES as $file) {
            if (is_int($file['error'])) {
                $error = $file['error'];

                if (!$onlySuccessful || $error === UPLOAD_ERR_OK) {
                    return true;
                }
            } else {
                /** @noinspection ForeachSourceInspection */
                foreach ($file['error'] as $error) {
                    if (!$onlySuccessful || $error === UPLOAD_ERR_OK) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Gets attached files as \ManaPHP\Http\Request\File instances
     *
     * @param bool $onlySuccessful
     *
     * @return \ManaPHP\Http\Request\File[]
     */
    public function getFiles($onlySuccessful = true)
    {
        $context = $this->_context;

        $r = [];

        /** @var $_FILES array */
        foreach ($context->_FILES as $key => $files) {
            if (isset($files[0])) {
                foreach ($files as $file) {
                    if (!$onlySuccessful || $file['error'] === UPLOAD_ERR_OK) {
                        $file['key'] = $key;

                        $r[] = new File($file);
                    }
                }
            } elseif (is_int($files['error'])) {
                $file = $files;
                if (!$onlySuccessful || $file['error'] === UPLOAD_ERR_OK) {
                    $file['key'] = $key;

                    $r[] = new File($file);
                }
            } else {
                $countFiles = count($files['error']);
                /** @noinspection ForeachInvariantsInspection */
                for ($i = 0; $i < $countFiles; $i++) {
                    if (!$onlySuccessful || $files['error'][$i] === UPLOAD_ERR_OK) {
                        $fileInfo = [
                            'key' => $key,
                            'name' => $files['name'][$i],
                            'type' => $files['type'][$i],
                            'tmp_name' => $files['tmp_name'][$i],
                            'error' => $files['error'][$i],
                            'size' => $files['size'][$i],
                        ];
                        $r[] = new File($fileInfo);
                    }
                }
            }
        }

        return $r;
    }

    /**
     * @param string $key
     *
     * @return \ManaPHP\Http\Request\FileInterface
     */
    public function getFile($key = null)
    {
        $files = $this->getFiles();

        if ($key === null) {
            if ($files) {
                return current($files);
            } else {
                throw new FileException('can not found any uploaded files');
            }
        } else {
            foreach ($files as $file) {
                if ($file->getKey() === $key) {
                    return $file;
                }
            }
            throw new FileException(['can not found uploaded `:key` file', 'key' => $key]);
        }
    }

    /**
     * Gets web page that refers active request. ie: http://www.google.com
     *
     * @return string
     */
    public function getReferer()
    {
        return strip_tags($this->getServer('HTTP_REFERER'));
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return strip_tags($this->getScheme() . '://' . $this->getServer('HTTP_HOST') . $this->getServer('REQUEST_URI'));
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return strip_tags($this->getServer('REQUEST_URI'));
    }

    /**
     * @param string $name
     *
     * @return string|null
     */
    public function getToken($name = 'token')
    {
        if ($token = $this->get($name, '')) {
            return $token;
        } elseif ($token = $this->getServer('HTTP_AUTHORIZATION')) {
            $parts = explode(' ', $token, 2);
            if ($parts[0] === 'Bearer' && count($parts) === 2) {
                return $parts[1];
            }
        }

        return null;
    }

    public function jsonSerialize()
    {
        return $this->_context;
    }
}