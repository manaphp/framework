<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\Cli\Command;

/**
 * @property-read \ManaPHP\Http\Acl\BuilderInterface   $aclBuilder
 * @property-read \ManaPHP\Http\AuthorizationInterface $authorization
 */
class AclCommand extends Command
{
    /**
     * list acl of controllers
     *
     * @param string $role
     *
     * @return void
     */
    public function listAction(string $role = ''): void
    {
        foreach ($this->aclBuilder->getControllers() as $controller) {
            /** @var \ManaPHP\Http\Controller $controllerInstance */
            $controllerInstance = $this->container->make($controller);
            $acl = $controllerInstance->getAcl();
            if ($role) {
                $actions = [];
                foreach ($this->aclBuilder->getActions($controller) as $action) {
                    if ($this->authorization->isAclAllowed($acl, $role, $action)) {
                        $actions[] = $action;
                    }
                }

                $this->console->writeLn($controller . ":\t " . implode(',', $actions));
            } else {
                $this->console->writeLn($controller . ":\t " . json_stringify($acl));
            }
        }
    }
}