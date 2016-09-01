<?php
namespace ManaPHP\Configure\Engine;

use ManaPHP\Configure\EngineInterface;
use ManaPHP\Configure\Engine\Json\Exception as JsonException;

class Json implements EngineInterface
{
    public function load($file)
    {
        $data = file_get_contents($file, true);
        if ($data === false) {
            throw new JsonException('`:file` configure file can not be loaded'/**m0db3b2b5cb242975b*/, ['file' => $file]);
        } else {
            return $data;
        }
    }
}