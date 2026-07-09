<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\EventSubscriber;

use Inachis\Entity\User\User;
use Inachis\Enum\Security\PermissionAction;
use Inachis\Enum\Security\PermissionResource;
use Inachis\Security\Authorisation\PermissionResolver;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscriber to enforce permissions across routes starting with /incc
 */
class SecurityPermissionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private PermissionResolver $permissionResolver
    ) {}

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

        // Only check routes starting with /incc
        if (!str_starts_with($path, '/incc')) {
            return;
        }

        // Exclude public paths under /incc
        if (preg_match('#^/incc/(login|forgot-password|new-password|logout|api/calculate-password-strength)($|/)#', $path)) {
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
        if (is_array($controller)) {
            $controllerClass = get_class($controller[0]);
            $method = $controller[1];
        } else {
            return;
        }

        // Handle whitelist of generic/shell endpoints that only require being logged in (ROLE_USER)
        if ($this->isWhitelisted($controllerClass, $method, $request, $user)) {
            return;
        }

        $permission = $this->resolvePermission($controllerClass, $method, $request);
        if ($permission === null) {
            // Fail-closed default: if we can't map a route, deny access to non-super-admins
            throw new AccessDeniedHttpException('Access Denied. You do not have permission to access this resource.');
        }

        [$resource, $action] = $permission;

        if (!$this->permissionResolver->hasPermission($user, $resource, $action)) {
            throw new AccessDeniedHttpException(sprintf(
                'Access Denied. You do not have the %s permission for %s.',
                $action->label(),
                $resource->label()
            ));
        }
    }

    /**
     * Determines whether a controller action is whitelisted for all authenticated users
     */
    private function isWhitelisted(string $controllerClass, string $method, $request, User $user): bool
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
            $targetUserUsername = $request->attributes->get('id');
            if ($user->getUsername() === $targetUserUsername) {
                return true;
            }
        }

        // 5. Editing/viewing own profile
        if (str_contains($controllerClass, 'AdminProfileController') && $method === 'edit') {
            $targetUserUsername = $request->attributes->get('id');
            if ($user->getUsername() === $targetUserUsername) {
                // Prevent editing of own roles if the request contains role updates,
                // but standard profile edits are allowed.
                // We'll let UserType handle role field visibility based on isGranted('ROLE_EDIT').
                return true;
            }
        }

        // 6. Waste
        if (str_contains($controllerClass, 'WasteController')) {
            return true;
        }

        return false;
    }

    /**
     * Maps controller class and method to [PermissionResource, PermissionAction]
     *
     * @return array{0: PermissionResource, 1: PermissionAction}|null
     */
    private function resolvePermission(string $controllerClass, string $method, $request): ?array
    {
        // 1. Pages and Posts
        if (str_contains($controllerClass, 'PageController') || str_contains($controllerClass, 'RevisionController') || str_contains($controllerClass, 'PostType')) {
            if ($method === 'list' || $method === 'getRevisions') {
                return [PermissionResource::PAGE, PermissionAction::VIEW];
            }
            if ($method === 'edit' || $method === 'save' || $method === 'compare') {
                if ($request->isMethod('POST')) {
                    $postData = $request->request->all('post');
                    if (isset($postData['delete'])) {
                        return [PermissionResource::PAGE, PermissionAction::DELETE];
                    }
                    if (isset($postData['publish'])) {
                        return [PermissionResource::PAGE, PermissionAction::PUBLISH];
                    }
                }
                $title = $request->attributes->get('title');
                if ($title === 'new' || $title === null) {
                    return [PermissionResource::PAGE, PermissionAction::CREATE];
                }
                return [PermissionResource::PAGE, PermissionAction::EDIT];
            }
        }

        // 2. Dialog page selector / gallery / bulk create
        if (str_contains($controllerClass, 'ContentSelectorController')) {
            return [PermissionResource::PAGE, PermissionAction::VIEW];
        }
        if (str_contains($controllerClass, 'BulkCreateController')) {
            return [PermissionResource::PAGE, PermissionAction::CREATE];
        }
        if (str_contains($controllerClass, 'ImageGalleryDialogController')) {
            return [PermissionResource::IMAGE, PermissionAction::VIEW];
        }
        if (str_contains($controllerClass, 'CategoryDialogController')) {
            return [PermissionResource::CATEGORY, PermissionAction::VIEW];
        }

        // 3. Page reviews
        if (str_contains($controllerClass, 'ReviewController') || str_contains($controllerClass, 'ReviewAssignController')) {
            return [PermissionResource::PAGE, PermissionAction::REVIEW];
        }

        // 4. Series
        if (str_contains($controllerClass, 'SeriesController')) {
            if ($method === 'list') {
                return [PermissionResource::SERIES, PermissionAction::VIEW];
            }
            if ($method === 'edit') {
                if ($request->isMethod('POST')) {
                    $seriesData = $request->request->all('series');
                    if (isset($seriesData['delete'])) {
                        return [PermissionResource::SERIES, PermissionAction::DELETE];
                    }
                }
                $seriesId = $request->attributes->get('id');
                if ($seriesId === 'new' || $seriesId === null) {
                    return [PermissionResource::SERIES, PermissionAction::CREATE];
                }
                return [PermissionResource::SERIES, PermissionAction::EDIT];
            }
        }

        // @todo: add downloads check to this
        if (str_contains($controllerClass, 'ResourceController')) {
            return [PermissionResource::IMAGE, PermissionAction::VIEW];
        }

        // 5. Roles and Permissions Management
        if (str_contains($controllerClass, 'RolesController')) {
            return [PermissionResource::ROLE, PermissionAction::MANAGE];
        }

        // 6. User Management (other users)
        if (str_contains($controllerClass, 'AdminProfileController') || str_contains($controllerClass, 'ChangePasswordController')) {
            if ($method === 'list') {
                return [PermissionResource::USER, PermissionAction::VIEW];
            }
            if ($method === 'edit' || $method === 'changePasswordTab') {
                if ($request->isMethod('POST')) {
                    $userData = $request->request->all('user');
                    if (isset($userData['delete'])) {
                        return [PermissionResource::USER, PermissionAction::DELETE];
                    }
                }
                $id = $request->attributes->get('id');
                if ($id === 'new') {
                    return [PermissionResource::USER, PermissionAction::CREATE];
                }
                return [PermissionResource::USER, PermissionAction::EDIT];
            }
        }

        // 7. Analytics
        if (str_contains($controllerClass, 'AnalyticsController') || str_contains($controllerClass, 'ContentAnalyticsController')) {
            return [PermissionResource::ANALYTICS, PermissionAction::VIEW];
        }

        // 8. System Status / Diagnostics
        if (str_contains($controllerClass, 'DiagnosticsController') || str_contains($controllerClass, 'LinkValidationController')) {
            return [PermissionResource::SYSTEM_STATUS, PermissionAction::VIEW];
        }

        // 9. Error Logs
        if (str_contains($controllerClass, 'LogController')) {
            return [PermissionResource::ERROR_LOG, PermissionAction::VIEW];
        }

        // 10. Email/DNS Settings
        if (str_contains($controllerClass, 'EmailSettingController')) {
            return [PermissionResource::EMAIL_DNS, PermissionAction::VIEW];
        }

        // 11. Maintenance Mode
        if (str_contains($controllerClass, 'MaintenanceController')) {
            if ($request->isMethod('POST')) {
                return [PermissionResource::MAINTENANCE, PermissionAction::EDIT];
            }
            return [PermissionResource::MAINTENANCE, PermissionAction::VIEW];
        }

        // 12. Import / Export
        if (str_contains($controllerClass, 'ImportController') || str_contains($controllerClass, 'ExportController')) {
            return [PermissionResource::IMPORT_EXPORT, PermissionAction::MANAGE];
        }

        // 13. Themes & Navigation Settings
        if (str_contains($controllerClass, 'ThemeController') || str_contains($controllerClass, 'SettingsIndexController') || str_contains($controllerClass, 'AppearanceController')) {
            return [PermissionResource::THEME, PermissionAction::MANAGE];
        }
        if (str_contains($controllerClass, 'NavigationTabController')) {
            return [PermissionResource::NAVIGATION, PermissionAction::VIEW];
        }

        // 14. URL redirects
        if (str_contains($controllerClass, 'UrlController')) {
            return [PermissionResource::PAGE, PermissionAction::EDIT];
        }

        return null;
    }
}
