<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\User;

use Inachis\Repository\User\UserRepository;
use Inachis\Service\User\UserProtectionServiceInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service for applying bulk actions to users
 */
readonly class UserBulkActionService
{
    /**
     * @param UserProtectionServiceInterface $userProtectionService
     * @param UserRepository $userRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        private UserProtectionServiceInterface $userProtectionService,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Apply a bulk action to users
     *
     * @param string $action
     * @param list<string> $ids
     * @return int
     */
    public function apply(string $action, array $ids): int
    {
        $users = [];

        foreach ($ids as $id) {
            /** @var \Inachis\Entity\User\User|null $user */
            $user = $this->userRepository->find($id);
            if (null === $user || empty($user->getUsername())) {
                continue;
            }
            $users[] = $user;
        }

        if (in_array($action, ['delete', 'disable'], true)) {
            $this->userProtectionService->assertAdministratorsCanBeRemoved($users);
        }

        $count = 0;

        foreach ($users as $user) {
            match ($action) {
                'delete'  => $user->setRemoved(true),
                'enable'  => $user->setActive(true),
                'disable' => $user->setActive(false),
                default => null,
            };
            $this->entityManager->persist($user);
            ++$count;
        }

        $this->entityManager->flush();
        return $count;
    }
}
