<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Twig;

use Inachis\Service\User\UserPreferenceProvider;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * Adds userPreference to the Twig globals for easy access to the signed-in user's preferences
 */
class UserPreferencesExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private UserPreferenceProvider $preferenceProvider,
        private Security $security
    ) {}

    /**
     * Will return userPreferences if the user is signed in
     *
     * @return array
     */
    public function getGlobals(): array
    {
        if ($this->security->getUser() && $this->security->isGranted('ROLE_ADMIN')) {
            return [
                'userPreference' => $this->preferenceProvider->get(),
            ];
        }
        return [];
    }
}
