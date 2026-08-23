<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\EventSubscriber;

use Inachis\Entity\User\User;
use Inachis\Enum\Security\PermissionAction;
use Inachis\Enum\Security\PermissionResource;
use Inachis\Security\Attribute\PermissionAttributeReader;
use Inachis\Security\Authorisation\PermissionResolver;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscriber to enforce permissions across routes starting with /incp.
 */
class SecurityPermissionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private PermissionAttributeReader $attributeReader,
        private PermissionResolver $permissionResolver,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Only check routes starting with /incp
        if (!str_starts_with($path, '/incp')) {
            return;
        }

        // Exclude public paths under /incp
        if (preg_match('#^/incp/(login|forgot-password|new-password|logout|api/calculate-password-strength)($|/)#', $path)) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            // Let Symfony's security system handle redirecting to login
            return;
        }

        // Super admins bypass all fine-grained checks
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $controller = $event->getController();
        if (is_array($controller) && is_object($controller[0])) {
            $controllerObject = $controller[0];
            $controllerClass = get_class($controllerObject);
            $method = (string) $controller[1];
        } else {
            return;
        }

        // Handle whitelist of generic/shell endpoints that only require being logged in (ROLE_USER)
        if ($this->isWhitelisted($controllerClass, $method, $request, $user)) {
            return;
        }

        $permissions = $this->attributeReader->getPermissions(
            $controllerObject,
            $method,
        );

        if ([] !== $permissions) {
            foreach ($permissions as $permission) {
                $allowed = false;
                foreach ($permission->resources() as $resource) {
                    if (
                        $this->permissionResolver->hasPermission(
                            $user,
                            $resource,
                            $permission->action,
                        )
                    ) {
                        $allowed = true;
                        break;
                    }
                }

                if (!$allowed) {
                    $resourceLabel = implode(', ', array_map(
                        static fn (PermissionResource $res): string => $res->label(),
                        $permission->resources(),
                    ));

                    throw new AccessDeniedHttpException(sprintf(
                        'Access denied. %s permission required for %s.',
                        $permission->action->label(),
                        $resourceLabel,
                    ));
                }
            }
        }
    }

    /**
     * Determines whether a controller action is whitelisted for all authenticated users.
     */
    private function isWhitelisted(string $controllerClass, string $method, Request $request, User $user): bool
    {
        // 1. Dashboard
        if (str_contains($controllerClass, 'DashboardController')) {
            return true;
        }

        // 2. Search
        if (str_contains($controllerClass, 'SearchController') || str_contains($controllerClass, 'SearchAPIController')) {
            return true;
        }

        // 3. Helper Confirmation / Dialogs
        if (str_contains($controllerClass, 'ConfirmationController') || str_contains($controllerClass, 'SessionTimeoutDialogController')) {
            return true;
        }

        // 4. Changing own password
        if (str_contains($controllerClass, 'ChangePasswordController')) {
            /** @var string|null $targetUserUsername */
            $targetUserUsername = $request->attributes->get('id');
            if ($user->getUsername() === $targetUserUsername) {
                return true;
            }
        }

        // 5. Editing/viewing own profile
        if (str_contains($controllerClass, 'AdminProfileController') && 'edit' === $method) {
            /** @var string|null $targetUserUsername */
            $targetUserUsername = $request->attributes->get('id');
            if ($user->getUsername() === $targetUserUsername) {
                return true;
            }
        }

        // 6. Waste
        if (str_contains($controllerClass, 'WasteController')) {
            return true;
        }

        return false;
    }
}
