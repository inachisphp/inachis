<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\User;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\{User,UserPreference};
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class used for retrieving the user's preferences
 */
class UserPreferenceProvider
{
    public function __construct(
        private Security $security,
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Returns the currently signed-in {@link User}'s preferences from the session
     * if available, or from the entity otherwise (and will then store in session to reduce lookups).
     * Unauthenticated users will return null.
     *
     * @return UserPreference|null
     */
    public function get(): ?UserPreference
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $session = $this->requestStack->getSession();
        if (!$user) {
            return null;
        }

        $preferences = $user->getPreferences();
        if ($preferences === null) {
            $preferences = new UserPreference($user);
            $user->setPreferences($preferences);
            $this->entityManager->persist($preferences);
        }

        $session->set('user_preferences', $preferences);

        return $preferences;
    }

    /**
     * Save changes to the user's preferences and refresh session cache
     * 
     * @var UserPreference $preferences
     */
    public function save(UserPreference $preferences): void
    {
        $this->entityManager->persist($preferences);
        $this->entityManager->flush();

        $session = $this->requestStack->getSession();
        $session->set('user_preferences', $preferences);
    }
}
