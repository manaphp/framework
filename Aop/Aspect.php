<?php
namespace ManaPHP\Aop;

use ManaPHP\Component;

/**
 * Class Aspect
 * @package ManaPHP\Aop
 * @property-read \ManaPHP\AopInterface $aop
 */
abstract class Aspect extends Component
{
    abstract public function register();
}