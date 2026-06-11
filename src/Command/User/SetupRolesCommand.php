<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */


namespace Inachis\Command\User;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\Security\Role;
use Inachis\Entity\Security\RolePermission;
use Inachis\Repository\Security\RoleRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

#[AsCommand(
    name: 'inachis:user:setup-roles',
    description: 'Create default roles and assign permissions.',
)]
class SetupRolesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RoleRepository $roleRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rolesData = [
            'admin' => [
                'description' => 'Full access to all resources.',
                'permissions' => [
                    ['action' => 'CREATE', 'resource' => '*'],
                    ['action' => 'EDIT',   'resource' => '*'],
                    ['action' => 'DELETE', 'resource' => '*'],
                    ['action' => 'VIEW',   'resource' => '*'],
                    ['action' => 'REVIEW', 'resource' => '*'],
                    ['action' => 'PUBLISH','resource' => '*'],
                ],
                'disableReview' => false,
            ],
            'editor' => [
                'description' => 'Can create, edit and view all content, but cannot delete or manage users.',
                'permissions' => [
                    ['action' => 'CREATE', 'resource' => '*'],
                    ['action' => 'EDIT',   'resource' => '*'],
                    ['action' => 'VIEW',   'resource' => '*'],
                    ['action' => 'REVIEW', 'resource' => '*'],
                    ['action' => 'PUBLISH','resource' => '*'],
                ],
                'disableReview' => false,
            ],
            'author' => [
                'description' => 'Can create and edit own content, view all.',
                'permissions' => [
                    ['action' => 'CREATE', 'resource' => '*'],
                    ['action' => 'EDIT',   'resource' => '*'],
                    ['action' => 'VIEW',   'resource' => '*'],
                ],
                'disableReview' => false,
            ],
            'contributor' => [
                'description' => 'Can view content only.',
                'permissions' => [
                    ['action' => 'VIEW', 'resource' => '*'],
                ],
                'disableReview' => false,
            ],
            'reviewer' => [
                'description' => 'Read‑only reviewer, can view and add review notes.',
                'permissions' => [
                    ['action' => 'VIEW',   'resource' => '*'],
                    ['action' => 'REVIEW', 'resource' => '*'],
                ],
                'disableReview' => false,
            ],
        ];

        $output->writeln('Creating default roles...');
        $progress = new ProgressBar($output, count($rolesData));
        $progress->start();

        foreach ($rolesData as $name => $data) {
            // Check if role already exists
            $existing = $this->roleRepository->findOneBy(['name' => $name]);
            if ($existing) {
                $role = $existing;
                $role->setDescription($data['description']);
                $role->setDisableReview($data['disableReview']);
            } else {
                $role = new Role();
                $role->setName($name);
                $role->setDescription($data['description']);
                $role->setDisableReview($data['disableReview']);
                $this->entityManager->persist($role);
            }

            // Clear existing permissions to avoid duplicates
            foreach ($role->getRolePermissions() as $perm) {
                $this->entityManager->remove($perm);
            }

            foreach ($data['permissions'] as $permData) {
                $perm = new RolePermission();
                $perm->setAction($permData['action']);
                $perm->setResource($permData['resource']);
                $perm->setRole($role);
                $this->entityManager->persist($perm);
            }

            $progress->advance();
        }

        $this->entityManager->flush();
        $progress->finish();
        $output->writeln('\nDefault roles and permissions have been set.');
        return Command::SUCCESS;
    }
}
