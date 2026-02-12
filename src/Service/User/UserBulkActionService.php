<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\User;


use Inachis\Repository\UserRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service for applying bulk actions to users
 */
readonly class UserBulkActionService
{
    /**
     * @param UserRepository $userRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Apply a bulk action to users
     * 
     * @param string $action
     * @param array<int> $ids
     * @return int
     */
    public function apply(string $action, array $ids): int
    {
        $count = 0;
        foreach ($ids as $id) {
            /** @var \Inachis\Entity\User|null $user */
            $user = $this->userRepository->find($id);
            if (null === $user || empty($user->getUsername())) {
                continue;
            }
            match ($action) {
                'delete'  => $user->setRemoved(true),
                'enable'  => $user->setActive(true),
                'disable' => $user->setActive(false),
                default   => null,
            };
            $user->setModDate(new DateTime());
            $this->entityManager->persist($user);
            $count++;
        }

        $this->entityManager->flush();
        return $count;
    }
}
