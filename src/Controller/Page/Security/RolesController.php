<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Security;

use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\Security\Role;
use Inachis\Entity\Security\RolePermission;
use Inachis\Enum\Security\PermissionAction;
use Inachis\Enum\Security\PermissionResource;
use Inachis\Form\RoleType;
use Inachis\Model\ContentQueryParameters;
use Inachis\Model\Page\ViewStateDefaults;
use Inachis\Repository\Content\CategoryRepository;
use Inachis\Repository\Security\RoleRepository;
use Inachis\Security\Attribute\RequiresPermission;
use Inachis\Service\Content\ViewStateManager;
use Inachis\Validator\Security\RolePermissionValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Controller for managing roles and role permissions.
 */
class RolesController extends AbstractInachisController
{
    /**
     * Lists all roles.
     *
     * @param RoleRepository $roleRepository
     * @param CategoryRepository $categoryRepository
     * @param RoleRepository $roleRepository
     * @param ViewStateManager $viewStateManager
     * @return Response The response the controller results in
     */
    #[Route(
        '/incp/security/roles',
        name: 'incp_admin_role_index',
        methods: ['GET', 'POST']
    )]
    #[RequiresPermission(
        resource: PermissionResource::ROLE,
        action: PermissionAction::MANAGE
    )]
    public function index(
        Request $request,
        CategoryRepository $categoryRepository,
        RoleRepository $roleRepository,
        ViewStateManager $viewStateManager,
    ): Response {
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        if (
            $form->isSubmitted() &&
            $form->isValid() &&
            !empty($request->request->all('items')
        )) {
            $items = $request->request->all('items');
            if ($request->request->has('clone')) {
                $count = 0;
                foreach ($items as $roleId) {
                    $source = $roleRepository->find($roleId);
                    if ($source === null) {
                        continue;
                    }
                    $clone = new Role();
                    $clone->setName($source->getName() . ' (Copy)')
                        ->setDescription($source->getDescription())
                        ->setDisableReview($source->isDisableReview())
                        ->setSystemRole(false);
                    foreach ($source->getRolePermissions() as $permission) {
                        $clonePermission = new RolePermission();
                        $clonePermission
                            ->setResource($permission->getResource())
                            ->setAction($permission->getAction());
                        $clone->addRolePermission($clonePermission);
                    }
                    $this->entityManager->persist($clone);
                    ++$count;
                }
                $this->entityManager->flush();
                $this->addFlash('success', "Cloned $count role(s).");

            } else if ($request->request->has('delete')) {
                $count = 0;
                foreach ($items as $roleId) {
                    $role = $roleRepository->find($roleId);
                    if ($role === null) {
                        continue;
                    }
                    if (!$role->canBeDeleted()) {
                        $this->addFlash(
                            'error',
                            sprintf(
                                'The role "%s" is currently assigned to %d user(s) and cannot be deleted.',
                                $role->getName(),
                                $role->getUserCount(),
                            )
                        );

                        return $this->redirectToRoute(
                            'incp_admin_role_edit',
                            [
                                'roleId' => (string) $role->getId(),
                            ]
                        );
                    }
                    $this->entityManager->remove($role);
                    ++$count;
                }
                $this->entityManager->flush();
                $this->addFlash('success', "Deleted $count role(s).");
            }

            return $this->redirectToRoute('incp_admin_role_index');
        }

        $params = $viewStateManager->build(
            $request,
            'roles',
            new ViewStateDefaults(
                sort: 'displayName asc',
                view: 'list',
            ),
            $categoryRepository,
        );

        // $contentQuery = $contentQueryParameters->process(
        //     $request,
        //     $categoryRepository,
        //     'admin',
        //     'displayName asc',
        // );
        $this->viewModel->page->title = 'Roles';
        $this->viewModel->page->tab = 'roles';
        return $this->render('inadmin/page/security/roles/list.html.twig', [
            'viewModel' => $this->viewModel,
            'form' => $form->createView(),
            'dataset' => $roleRepository->getFiltered(
                $params->getFilters(),
                $params->getLimit(),
                $params->getOffset(),
            ),
            'query' => $params,
        ]);
    }

    /**
     * Creates a new role or edits an existing one.
     *
     * The route parameter {role-id} is either the UUID of an existing role
     * or the literal string "new" for creating a fresh role.
     *
     * @param Request $request
     * @param RoleRepository $roleRepository
     * @param string $roleId
     * @return Response The response the controller results in
     */
    #[Route(
        '/incp/security/roles/{roleId}',
        name: 'incp_admin_role_edit',
        requirements: ['roleId' => '[0-9a-f\-]{36}|new'],
        methods: ['GET', 'POST']
    )]
    #[RequiresPermission(
        resource: PermissionResource::ROLE,
        action: PermissionAction::MANAGE
    )]
    public function edit(
        Request $request,
        RolePermissionValidator $rolePermissionValidator,
        RoleRepository $roleRepository,
        string $roleId,
    ): Response {
        $isNew = ($roleId === 'new');

        if ($isNew) {
            $role = new Role();
        } else {
            $role = $roleRepository->find($roleId);
            if ($role === null) {
                $this->addFlash('error', 'Role not found.');
                return $this->redirectToRoute('incp_admin_role_index');
            }
        }

        $form = $this->createForm(RoleType::class, $role);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $delete = $form->has('delete') ? $form->get('delete') : null;
            if (
                $delete instanceof \Symfony\Component\Form\ClickableInterface &&
                $delete->isClicked() && !$isNew
            ) {
                if (!$role->canBeDeleted()) {
                    $this->addFlash(
                        'error',
                        sprintf(
                            'The role "%s" is currently assigned to %d user(s) and cannot be deleted.',
                            $role->getName(),
                            $role->getUserCount(),
                        )
                    );

                    return $this->redirectToRoute(
                        'incp_admin_role_edit',
                        [
                            'roleId' => (string) $role->getId(),
                        ]
                    );
                }
                $roleName = $role->getName();
                $this->entityManager->remove($role);
                $this->entityManager->flush();
                $this->addFlash('success', "Role '$roleName' has been deleted.");
                return $this->redirectToRoute('incp_admin_role_index');
            }

            // Synchronise permissions: rebuild from posted checkboxes.
            $this->syncPermissions($request, $role);

            $warnings = $rolePermissionValidator->validate(
                $request->request->all('permissions')
            );

            foreach ($warnings as $warning) {
                $this->addFlash('warning', $warning);
            }

            $this->entityManager->persist($role);
            $this->entityManager->flush();

            $this->addFlash('success', 'Role saved.');
            return $this->redirectToRoute('incp_admin_role_edit', [
                'roleId' => (string) $role->getId(),
            ]);
        }

        // Build a structured permission matrix to pass to the template.
        $permissionMatrix = $this->buildPermissionMatrix($role);

        $permissionImplications = [];
        foreach (PermissionAction::cases() as $action) {
            $permissionImplications[strtolower($action->value)] = array_map(
                fn(PermissionAction $implied) => strtolower($implied->value),
                $action->requires()
            );
        }

        $this->viewModel->page->title = $isNew ? 'New Role' : 'Edit Role';
        $this->viewModel->page->tab = 'roles';
        return $this->render('inadmin/page/security/roles/edit.html.twig', [
            'viewModel' => $this->viewModel,
            'actions' => PermissionAction::cases(),
            'form' => $form->createView(),
            'permissionGroups' => PermissionResource::grouped(),
            'permissionImplications' => $permissionImplications,
            'permissionMatrix' => $permissionMatrix,
            'resources' => PermissionResource::cases(),
            'role' => $role,
        ]);
    }

    /**
     * Rebuilds the role's permission collection from the raw POST data.
     *
     * The template renders a grid of checkboxes named
     * `permissions[{resource}][{action}]`; this method reflects those
     * checkboxes back onto the Role entity using orphan-removal to handle
     * deletions automatically.
     *
     * @param Request $request
     * @param Role $role
     */
    private function syncPermissions(Request $request, Role $role): void
    {
        /** @var array<string, array<string, mixed>> $posted */
        $posted = $request->request->all('permissions');
        if (!is_array($posted)) {
            $posted = [];
        }

        // Remove all existing permissions; orphanRemoval will delete them.
        foreach ($role->getRolePermissions() as $existing) {
            $role->removeRolePermission($existing);
        }

        foreach ($posted as $resource => $actions) {
            $resourceEnum = PermissionResource::tryFrom($resource);
            if ($resourceEnum === null) {
                continue;
            }

            foreach (array_keys($actions) as $action) {
                $actionEnum = PermissionAction::tryFrom($action);

                if (
                    $actionEnum === null ||
                    !in_array($actionEnum, $resourceEnum->actions(), true)
                ) {
                    continue;
                }

                $perm = new RolePermission();
                $perm->setResource($resourceEnum);
                $perm->setAction($actionEnum);

                $role->addRolePermission($perm);
            }
        }
    }

    /**
     * Builds the permissions matrix
     *
     * @param Role $role
     * @return array<string, array<string, string>>
     */
    private function buildPermissionMatrix(Role $role): array
    {
        $granted = [];

        foreach ($role->getRolePermissions() as $perm) {
            $granted[$perm->getResource()->value][$perm->getAction()->value] = true;
        }

        $matrix = [];

        foreach (PermissionResource::cases() as $resource) {
            foreach ($resource->actions() as $action) {
                $matrix[$resource->value][$action->value] =
                    $granted[$resource->value][$action->value] ?? false;
            }
        }

        return $matrix;
    }
}
