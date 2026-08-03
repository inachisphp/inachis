<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Command\Security;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\Security\Role;
use Inachis\Entity\Security\RolePermission;
use Inachis\Enum\Security\PermissionAction;
use Inachis\Enum\Security\PermissionResource;
use Inachis\Repository\Security\RoleRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'inachis:security:setup-roles',
    description: 'Creates or updates the default system roles.'
)]
class SetupRolesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RoleRepository $roleRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'reset',
            null,
            InputOption::VALUE_NONE,
            'Delete all roles before recreating them'
        );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        if ($input->getOption('reset')) {
            foreach ($this->roleRepository->findAll() as $role) {
                foreach ($role->getUsers() as $user) {
                    $user->removeAssignedRole($role);
                }
                $this->entityManager->remove($role);
            }

            $this->entityManager->flush();
        }

        $roles = $this->getDefaultRoles();

        $output->writeln('<info>Creating system roles...</info>');

        $progress = new ProgressBar($output, count($roles));
        $progress->start();

        foreach ($roles as $roleData) {
            $role = $this->roleRepository->findOneBy([
                'slug' => $roleData['slug'],
            ]);

            if (!$role instanceof Role) {
                $role = new Role();
                $role->setIdentifier($roleData['slug']);
            }

            $role
                ->setName($roleData['name'])
                ->setDescription($roleData['description'])
                ->setDisableReview(false)
                ->setSystemRole(true);

            foreach ($role->getRolePermissions() as $permission) {
                $role->removeRolePermission($permission);
                $this->entityManager->remove($permission);
            }

            foreach ($roleData['permissions'] as [$resource, $action]) {
                $permission = new RolePermission();
                $permission
                    ->setRole($role)
                    ->setResource($resource)
                    ->setAction($action);

                $role->addRolePermission($permission);
            }

            $this->entityManager->persist($role);

            $progress->advance();
        }

        $this->entityManager->flush();

        $progress->finish();

        $output->writeln('');
        $output->writeln('<info>System roles updated.</info>');

        return Command::SUCCESS;
    }

    /**
     * Returns an array of the default roles
     *
     * @return array<int, array{
     *     slug:string,
     *     name:string,
     *     description:string,
     *     permissions:array<int,array{PermissionResource,PermissionAction}>
     * }>
     */
    private function getDefaultRoles(): array
    {
        return [
            [
                'slug' => 'admin',
                'name' => 'Administrator',
                'description' => 'Full access to all functionality.',
                'permissions' => $this->allPermissions(),
            ],

            [
                'slug' => 'editor',
                'name' => 'Editor',
                'description' => 'Can manage and publish all content.',
                'permissions' => [
                    ...$this->resourcePermissions(
                        PermissionResource::PAGE,
                        PermissionResource::SERIES,
                        PermissionResource::CATEGORY,
                        PermissionResource::TAG,
                        PermissionResource::IMAGE,
                    ),

                    [PermissionResource::ANALYTICS, PermissionAction::VIEW],
                    [PermissionResource::AUDIT_LOG, PermissionAction::VIEW],
                    [PermissionResource::SYSTEM_STATUS, PermissionAction::VIEW],
                    [PermissionResource::EMAIL_DNS, PermissionAction::VIEW],
                    [PermissionResource::STORAGE, PermissionAction::VIEW],
                ],
            ],

            [
                'slug' => 'author',
                'name' => 'Author',
                'description' => 'Can create and edit content for publication.',
                'permissions' => [
                    [PermissionResource::PAGE, PermissionAction::VIEW],
                    [PermissionResource::PAGE, PermissionAction::CREATE],
                    [PermissionResource::PAGE, PermissionAction::EDIT],

                    [PermissionResource::SERIES, PermissionAction::VIEW],
                    [PermissionResource::SERIES, PermissionAction::CREATE],
                    [PermissionResource::SERIES, PermissionAction::EDIT],

                    [PermissionResource::CATEGORY, PermissionAction::VIEW],
                    [PermissionResource::TAG, PermissionAction::VIEW],
                    [PermissionResource::IMAGE, PermissionAction::VIEW],

                    [PermissionResource::IMAGE, PermissionAction::CREATE],
                ],
            ],

            [
                'slug' => 'contributor',
                'name' => 'Contributor',
                'description' => 'Can submit draft content.',
                'permissions' => [
                    [PermissionResource::PAGE, PermissionAction::VIEW],
                    [PermissionResource::PAGE, PermissionAction::CREATE],

                    [PermissionResource::SERIES, PermissionAction::VIEW],

                    [PermissionResource::CATEGORY, PermissionAction::VIEW],
                    [PermissionResource::TAG, PermissionAction::VIEW],
                    [PermissionResource::IMAGE, PermissionAction::VIEW],
                ],
            ],

            [
                'slug' => 'reviewer',
                'name' => 'Read-only Reviewer',
                'description' => 'Can review content awaiting publication.',
                'permissions' => [
                    [PermissionResource::PAGE, PermissionAction::VIEW],
                    [PermissionResource::PAGE, PermissionAction::REVIEW],

                    [PermissionResource::SERIES, PermissionAction::VIEW],
                    [PermissionResource::SERIES, PermissionAction::REVIEW],

                    [PermissionResource::AUDIT_LOG, PermissionAction::VIEW],
                ],
            ],
        ];
    }

    /**
     * @return array<int,array{PermissionResource,PermissionAction}>
     */
    private function allPermissions(): array
    {
        $permissions = [];

        foreach (PermissionResource::cases() as $resource) {
            foreach ($resource->actions() as $action) {
                $permissions[] = [$resource, $action];
            }
        }

        return $permissions;
    }

    /**
     * @return array<int,array{PermissionResource,PermissionAction}>
     */
    private function resourcePermissions(
        PermissionResource ...$resources
    ): array {
        $permissions = [];

        foreach ($resources as $resource) {
            foreach ($resource->actions() as $action) {
                $permissions[] = [$resource, $action];
            }
        }

        return $permissions;
    }
}
