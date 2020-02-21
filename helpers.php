<?php

use ManaPHP\Di;
use ManaPHP\Exception\AbortException;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\JsonException;
use ManaPHP\Exception\NotSupportedException;
use Swoole\Coroutine;

if (!function_exists('spl_object_id')) {
    function spl_object_id($object)
    {
        // https://github.com/akihiromukae/sample1/blob/1dc7b6e49684c882ef39476071179421fbd1e18e/vendor/phan/phan/src/spl_object_id.php
        $hash = spl_object_hash($object);
        return intval(PHP_INT_SIZE === 8 ? substr($hash, 1, 15) : substr($hash, 9, 7), 16);
    }
}

defined('JSON_THROW_ON_ERROR') or define('JSON_THROW_ON_ERROR', 0);

if (!function_exists('json_parse')) {
    /**
     * @param string $str
     *
     * @return mixed
     */
    function json_parse($str)
    {
        /** @noinspection NotOptimalIfConditionsInspection */
        if (($json = json_decode($str, true, 16, JSON_THROW_ON_ERROR)) === null && $str !== 'null') {
            throw new JsonException('json_parse failed: ' . $str);
        }

        return $json;
    }
}

if (!function_exists('json_stringify')) {
    /**
     * @param mixed $json
     * @param int   $options
     *
     * @return string
     */
    function json_stringify($json, $options = 0)
    {
        if (($str = json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR | $options, 16)) === false) {
            throw new JsonException('json_stringify failed');
        }

        return $str;
    }
}

if (!function_exists('xml_decode')) {
    /**
     * @param string $xml
     *
     * @return array|null
     */
    function xml_decode($xml)
    {
        if (($ret = @simplexml_load_string($xml, null, LIBXML_NOCDATA | LIBXML_NOBLANKS)) === false) {
            return null;
        }

        $ret = (array)$ret;

        foreach ($ret as $value) {
            if (!is_scalar($value) && $value !== null) {
                return json_decode(json_encode($ret), true);
            }
        }

        return $ret;
    }
}

if (!function_exists('di')) {
    /**
     * @param string $name
     *
     * @return mixed
     */
    function di($name = null)
    {
        static $di;
        if (!$di) {
            $di = Di::getDefault();
        }

        return $name === null ? $di : $di->getShared($name);
    }
}

if (!function_exists('env')) {
    /**
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    function env($key = null, $default = null)
    {
        return di('dotenv')->get($key, $default);
    }
}

if (!function_exists('param_get')) {
    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    function param_get($name, $default = null)
    {
        return di('configure')->getParam($name, $default);
    }
}

if (!function_exists('log_debug')) {
    /**
     * @param string|array $message
     * @param string       $category
     *
     * @return void
     */
    function log_debug($message, $category = null)
    {
        di('logger')->debug($message, $category);
    }
}

if (!function_exists('log_info')) {
    /**
     * @param string|array $message
     * @param string       $category
     *
     * @return void
     */
    function log_info($message, $category = null)
    {
        di('logger')->info($message, $category);
    }
}

if (!function_exists('log_warn')) {
    /**
     * @param string|array $message
     * @param string       $category
     *
     * @return void
     */
    function log_warn($message, $category = null)
    {
        di('logger')->warn($message, $category);
    }
}

if (!function_exists('log_error')) {
    /**
     * @param string|array $message
     * @param string       $category
     *
     * @return void
     */
    function log_error($message, $category = null)
    {
        di('logger')->error($message, $category);
    }
}

if (!function_exists('log_fatal')) {
    /**
     * @param string|array $message
     * @param string       $category
     *
     * @return void
     */
    function log_fatal($message, $category = null)
    {
        di('logger')->fatal($message, $category);
    }
}

if (!function_exists('path')) {
    /**
     * @param string $path
     *
     * @return string
     */
    function path($path)
    {
        return $path ? di('alias')->resolve($path) : di('alias')->get();
    }
}

if (!function_exists('jwt_encode')) {
    /**
     * @param array  $claims
     * @param int    $ttl
     * @param string $scope
     *
     * @return string
     */
    function jwt_encode($claims, $ttl, $scope)
    {
        return di('jwt')->encode($claims, $ttl, $scope);
    }
}

if (!function_exists('jwt_decode')) {
    /**
     * @param string $token
     * @param string $scope
     * @param bool   $verify
     *
     * @return array
     */
    function jwt_decode($token, $scope, $verify = true)
    {
        return di('jwt')->decode($token, $scope, $verify);
    }
}

if (!function_exists('jwt_verify')) {
    /**
     * @param string $token
     * @param string $scope
     *
     * @return void
     */
    function jwt_verify($token, $scope)
    {
        di('jwt')->decode($token, $scope);
    }
}

if (!function_exists('input')) {
    /**
     * @param string $name
     * @param mixed  $defaultOrRules =\PHPSTORM_META\validator_rule()
     *
     * @return mixed
     */
    function input($name = null, $defaultOrRules = null)
    {
        static $request;
        if (!$request) {
            $request = di('request');
        }

        if ($defaultOrRules && is_array($defaultOrRules)) {
            $value = $request->get($name, $defaultOrRules['default'] ?? null);
            return $request->validator->validateValue($name, $value, $defaultOrRules);
        } else {
            return $request->get($name, $defaultOrRules);
        }
    }
}

if (!function_exists('client_ip')) {
    /**
     * @return string
     */
    function client_ip()
    {
        return di('request')->getClientIp();
    }
}

if (!function_exists('curl')) {
    /**
     * @param string          $type
     * @param string|array    $url
     * @param string|array    $body
     * @param array|string    $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    function curl($type, $url, $body = null, $headers = [], $options = [])
    {
        return di('httpClient')->request($type, $url, $body, $headers, $options);
    }
}

if (!function_exists('curl_get')) {
    /**
     * @param string|array    $url
     * @param array|string    $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    function curl_get($url, $headers = [], $options = [])
    {
        return di('httpClient')->get($url, $headers, $options);
    }
}

if (!function_exists('curl_post')) {
    /**
     * @param string|array    $url
     * @param string|array    $body
     * @param array|string    $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    function curl_post($url, $body = null, $headers = [], $options = [])
    {
        return di('httpClient')->post($url, $body, $headers, $options);
    }
}

if (!function_exists('download')) {
    /**
     * @param string|array     $files
     * @param string|int|array $options
     *
     * @return string|array
     */
    function download($files, $options = [])
    {
        return di('httpClient')->download($files, $options);
    }
}

if (!function_exists('rest')) {
    /**
     * @param string          $type
     * @param string|array    $url
     * @param string|array    $body
     * @param array|string    $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    function rest($type, $url, $body = null, $headers = [], $options = [])
    {
        return di('restClient')->rest($type, $url, $body, $headers, $options);
    }
}

if (!function_exists('rest_get')) {
    /**
     * @param string|array    $url
     * @param array|string    $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    function rest_get($url, $headers = [], $options = [])
    {
        return di('restClient')->rest('GET', $url, null, $headers, $options);
    }
}

if (!function_exists('rest_post')) {
    /**
     * @param string|array    $url
     * @param string|array    $body
     * @param array|string    $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    function rest_post($url, $body, $headers = [], $options = [])
    {
        return di('restClient')->rest('POST', $url, $body, $headers, $options);
    }
}

if (!function_exists('rest_put')) {
    /**
     * @param string|array    $url
     * @param string|array    $body
     * @param array|string    $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    function rest_put($url, $body, $headers = [], $options = [])
    {
        return di('restClient')->rest('PUT', $url, $body, $headers, $options);
    }
}

if (!function_exists('rest_patch')) {
    /**
     * @param string|array    $url
     * @param string|array    $body
     * @param array|string    $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    function rest_patch($url, $body, $headers = [], $options = [])
    {
        return di('restClient')->rest('PATCH', $url, $body, $headers, $options);
    }
}

if (!function_exists('rest_delete')) {
    /**
     * @param string|array    $url
     * @param array|string    $headers
     * @param array|int|float $options
     *
     * @return \ManaPHP\Http\Client\Response
     */
    function rest_delete($url, $headers = [], $options = [])
    {
        return di('restClient')->rest('DELETE', $url, null, $headers, $options);
    }
}

if (!function_exists('render')) {
    /**
     * @param string $file
     * @param array  $vars
     *
     * @return string
     */
    function render($file, $vars = [])
    {
        return di('renderer')->render($file, $vars);
    }
}

if (!function_exists('abort')) {

    /**
     * @param string $message
     * @param int    $code
     *
     * @throws \ManaPHP\Exception\AbortException
     */
    function abort($message = null, $code = 1)
    {
        if ($message === null) {
            null;
        } elseif ($code === null) {
            di('response')->setContent($message);
        } else {
            di('response')->setJsonContent(['code' => $code, 'message' => $message]);
        }

        throw new AbortException();
    }
}

if (!function_exists('dd')) {
    function dd()
    {
        if (MANAPHP_COROUTINE_ENABLED) {
            /** @noinspection PhpUndefinedMethodInspection */
            $trace = Coroutine::getBackTrace(0, DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
        } else {
            $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
        }
        echo var_export($trace['file'] . ':' . $trace['line']), PHP_EOL;
        foreach (func_get_args() as $arg) {
            echo var_export($arg), PHP_EOL;
        }
        throw new AbortException();
    }
}

if (!function_exists('seconds')) {
    /**
     * @param string $str
     *
     * @return int
     */
    function seconds($str)
    {
        if (preg_match('#^([\d.]+)([smhdMy]?)$#', $str, $match)) {
            $units = ['' => 1, 's' => 1, 'm' => 60, 'h' => 3600, 'd' => 86400, 'M' => 2592000, 'y' => 31536000];
            return $match[1] * $units[$match[2]];
        } elseif (($r = strtotime($str, 0)) !== false) {
            return $r;
        } else {
            throw new InvalidValueException(['`:str` string is not a valid seconds expression', 'str' => $str]);
        }
    }
}

if (!function_exists('e')) {
    /**
     * @param string $value
     * @param bool   $doubleEncode
     *
     * @return string
     */
    function e($value, $doubleEncode = true)
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', $doubleEncode);
    }
}

if (!function_exists('t')) {
    /**
     * @param string $id
     * @param array  $bind
     *
     * @return string
     */
    function t($id, $bind = [])
    {
        return di('translator')->translate($id, $bind);
    }
}

if (!function_exists('image_create')) {
    /**
     * @param string $file
     *
     * @return \ManaPHP\ImageInterface
     */
    function image_create($file)
    {
        if (extension_loaded('imagick')) {
            return Di::getDefault()->get('ManaPHP\Image\Adapter\Imagick', [$file]);
        } elseif (extension_loaded('gd')) {
            return Di::getDefault()->get('ManaPHP\Image\Adapter\Gd', [$file]);
        } else {
            throw new NotSupportedException('neither `imagic` nor `gd` extension is loaded');
        }
    }
}