<?php
declare(strict_types=1);

namespace ManaPHP\Token;

use ManaPHP\Dumping\Dumper;

class JwtDumper extends Dumper
{
    public function dump(object $object): array
    {
        $data = parent::dump($object);
        $data['key'] = '***';

        return $data;
    }
}