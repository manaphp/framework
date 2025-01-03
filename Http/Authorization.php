<?php

declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Coroutine\ContextAware;
use ManaPHP\Coroutine\ContextManagerInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\ForbiddenException;
use ManaPHP\Helper\Str;
use ManaPHP\Http\Authorization\RoleRepositoryInterface;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Identifying\Identity\NoCredentialException;
use ManaPHP\Identifying\IdentityInterface;
use ReflectionMethod;
use function basename;
use function explode;
use function in_array;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function strpos;
use function substr;

class Authorization implements AuthorizationInterface, ContextAware
{
    #[Autowired] protected ContextManagerInterface $contextManager;
    #[Autowired] protected IdentityInterface $identity;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected ControllersInterface $controllers;
    #[Autowired] protected RoleRepositoryInterface $roleRepository;

    public function getContext(): AuthorizationContext
    {
        return $this->contextManager->getContext($this);
    }

    public function getPermission(string $controller, string $action): string
    {
        $controller = str_replace('\\', '.', $controller);
        $controller = basename($controller, 'Controller');
        $controller = str_replace('.Controllers.', '.', $controller);
        $controller = substr($controller, strpos($controller, '.') + 1);
        if (str_starts_with($controller, 'Areas.')) {
            $controller = substr($controller, 6);
        }
        return Str::hyphen($controller) . '::' . Str::hyphen(basename($action, 'Action'));
    }

    public function getAllowed(string $role): string
    {
        $context = $this->getContext();

        if (!isset($context->role_permissions[$role])) {
            $permissions = $this->roleRepository->getPermissions($role) ?? '';
            return $context->role_permissions[$role] = ",$permissions,";
        } else {
            return $context->role_permissions[$role];
        }
    }

    protected function getAuthorize(string $controller, string $action): ?Authorize
    {
        $rMethod = new ReflectionMethod($controller, $action);

        if (($attribute = $rMethod->getAttributes(Authorize::class)[0] ?? null) === null) {
            $rClass = $rMethod->getDeclaringClass();
            if (($attribute = $rClass->getAttributes(Authorize::class)[0] ?? null) === null) {
                return null;
            }
        }

        return $attribute->newInstance();
    }

    protected function isAllowedInternal(string $permission, string $role): bool
    {
        return str_contains($this->getAllowed($role), ",$permission,");
    }

    public function isAllowed(string $permission, ?array $roles = null): bool
    {
        $roles = $roles ?? $this->identity->getRoles();

        if (in_array(Authorize::ADMIN, $roles, true)) {
            return true;
        }

        if (str_contains($permission, '\\')) {
            list($controller, $action) = explode('::', $permission);

            if (($authorize = $this->getAuthorize($controller, $action)) !== null) {
                if (in_array(Authorize::GUEST, $authorize->roles, true)) {
                    return true;
                }

                if ($roles === []) {
                    return false;
                }

                if (in_array(Authorize::USER, $authorize->roles, true)) {
                    return true;
                }

                foreach ($authorize->roles as $role) {
                    if (in_array($role, $roles, true)) {
                        return true;
                    }
                }
            }
            $permission = $this->getPermission($controller, $action);
        }

        if ($this->isAllowedInternal($permission, Authorize::GUEST)) {
            return true;
        }

        if ($roles === []) {
            return false;
        }

        if ($this->isAllowedInternal($permission, Authorize::USER)) {
            return true;
        }

        foreach ($roles as $role) {
            if ($this->isAllowedInternal($permission, $role)) {
                return true;
            }
        }

        return false;
    }

    public function authorize(string $handler): void
    {
        if ($this->isAllowed($handler)) {
            return;
        }

        if ($this->identity->isGuest()) {
            if ($this->request->isAjax()) {
                throw new NoCredentialException('No Credential or Invalid Credential');
            } else {
                $redirect = $this->request->input('redirect', $this->request->url());
                $this->response->redirect(["/login?redirect=$redirect"]);
            }
        } else {
            throw new ForbiddenException('Access denied to resource');
        }
    }
}
