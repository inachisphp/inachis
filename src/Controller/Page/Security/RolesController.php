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
use Inachis\Form\RoleType;
use Inachis\Model\ContentQueryParameters;
use Inachis\Repository\Content\CategoryRepository;
use Inachis\Repository\Security\RoleRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for managing roles and role permissions.
 */
#[IsGranted('ROLE_ADMIN')]
class RolesController extends AbstractInachisController
{
    /**
     * Lists all roles.
     *
     * @param RoleRepository $roleRepository
     * @param CategoryRepository $categoryRepository
     * @param ContentQueryParameters $contentQueryParameters
     * @return Response The response the controller results in
     */
    #[Route('/incc/security/roles', name: 'incc_admin_role_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        CategoryRepository $categoryRepository,
        ContentQueryParameters $contentQueryParameters,
        RoleRepository $roleRepository,
    ): Response {
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && !empty($request->request->all('items'))) {
            $items = $request->request->all('items') ?? [];
            if ($request->request->has('delete') && !empty($items)) {
                $count = 0;
                foreach ($items as $roleId) {
                    $role = $roleRepository->find($roleId);
                    if ($role !== null) {
                        $this->entityManager->remove($role);
                        ++$count;
                    }
                }
                $this->entityManager->flush();
                $this->addFlash('success', "Deleted $count role(s).");
            }

            return $this->redirectToRoute('incc_admin_role_index');
        }

        $contentQuery = $contentQueryParameters->process(
            $request,
            $categoryRepository,
            'admin',
            'displayName asc',
        );
        $this->data['form'] = $form->createView();
        $this->data['dataset'] = $roleRepository->getFiltered(
            $contentQuery['filters'],
            $contentQuery['offset'],
            $contentQuery['limit'],
        );
        $this->data['query'] = $contentQuery;
        $this->data['page']['title']  = 'Roles';
        $this->data['page']['tab']    = 'roles';

        return $this->render('inadmin/page/security/roles/list.html.twig', $this->data);
    }

    /**
     * Creates a new role or edits an existing one.
     *
     * The route parameter {role-id} is either the UUID of an existing role
     * or the literal string "new" for creating a fresh role.
     *
     * @param Request        $request
     * @param RoleRepository $roleRepository
     * @param string         $roleId
     * @return Response The response the controller results in
     */
    #[Route(
        '/incc/security/roles/{roleId}',
        name: 'incc_admin_role_edit',
        requirements: ['roleId' => '[0-9a-f\-]{36}|new'],
        methods: ['GET', 'POST']
    )]
    public function edit(
        Request $request,
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
                return $this->redirectToRoute('incc_admin_role_index');
            }
        }

        $form = $this->createForm(RoleType::class, $role);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $clickedButton = $form->getClickedButton()?->getName();

            if ($clickedButton === 'delete' && !$isNew) {
                $roleName = $role->getName();
                $this->entityManager->remove($role);
                $this->entityManager->flush();
                $this->addFlash('success', "Role '$roleName' has been deleted.");
                return $this->redirectToRoute('incc_admin_role_index');
            }

            // Synchronise permissions: rebuild from posted checkboxes.
            $this->syncPermissions($request, $role);

            $this->entityManager->persist($role);
            $this->entityManager->flush();

            $this->addFlash('success', 'Role saved.');
            return $this->redirectToRoute('incc_admin_role_edit', [
                'roleId' => (string) $role->getId(),
            ]);
        }

        // Build a structured permission matrix to pass to the template.
        $permissionMatrix = $this->buildPermissionMatrix($role);

        $this->data['role']             = $role;
        $this->data['form']             = $form->createView();
        $this->data['permissionMatrix'] = $permissionMatrix;
        $this->data['page']['title']    = $isNew ? 'New Role' : 'Edit Role';
        $this->data['page']['tab']      = 'roles';

        return $this->render('inadmin/page/security/roles/edit.html.twig', $this->data);
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
     * @param Role    $role
     * @return void
     */
    private function syncPermissions(Request $request, Role $role): void
    {
        $posted = $request->request->all('permissions') ?? [];

        // Remove all existing permissions; orphanRemoval will delete them.
        foreach ($role->getRolePermissions() as $existing) {
            $role->removeRolePermission($existing);
        }

        foreach ($posted as $resource => $actions) {
            foreach (array_keys($actions) as $action) {
                $perm = new RolePermission();
                $perm->setResource(strtoupper((string) $resource));
                $perm->setAction(strtoupper((string) $action));
                $role->addRolePermission($perm);
            }
        }
    }

    /**
     * Builds a matrix of [resource => [action => bool]] for the template.
     *
     * @param Role $role
     * @return array<string, array<string, bool>>
     */
    private function buildPermissionMatrix(Role $role): array
    {
        $resources = ['PAGE', 'SERIES', 'IMAGE', 'TAG', 'CATEGORY'];
        $actions   = ['VIEW', 'CREATE', 'EDIT', 'DELETE', 'REVIEW', 'PUBLISH'];

        $granted = [];
        foreach ($role->getRolePermissions() as $perm) {
            $resource = $perm->getResource();
            $action   = $perm->getAction();
            // Wildcard '*' grants everything
            if ($resource === '*') {
                foreach ($resources as $r) {
                    $granted[$r][$action] = true;
                }
            } else {
                $granted[$resource][$action] = true;
            }
        }

        $matrix = [];
        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                $matrix[$resource][$action] = $granted[$resource][$action] ?? false;
            }
        }

        return $matrix;
    }
}
