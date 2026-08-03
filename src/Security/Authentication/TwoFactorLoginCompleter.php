<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Security\Authentication;

use Inachis\Entity\User\User;
use Inachis\Enum\Security\LoginResultType;
use Inachis\Security\Authentication\LoginSuccessRecorder;
use Inachis\Security\Authentication\TrustedDeviceManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Manages the security state and completion of pending 2FA login attempts.
 */
class TwoFactorLoginCompleter
{
    public function __construct(
        private readonly LoginSuccessRecorder $loginSuccessRecorder,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Security $security,
        private readonly TrustedDeviceManager $trustedDeviceManager,
    ) {}

    /**
     * Validates that the user is logged in and has a pending 2FA challenge state.
     * Clears the pending flag immediately if the user object is invalid.
     */
    public function isValid(Request $request): bool
    {
        $session = $request->getSession();
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            $session->remove('security.totp_pending');
            return false;
        }

        return $session->has('security.totp_pending');
    }

    /**
     * Complete a pending two-factor authentication.
     * Responsible for:
     * - clearing the pending 2FA session state
     * - redirecting the user to their destination
     *
     * Used by both 2FA codes, and recovery code routes
     *
     * @param Request $request
     * @param LoginResultType $loginType
     * @param bool $trustDevice Whether to issue a trusted device cookie
     * @return RedirectResponse
     */
    public function complete(
        Request $request,
        LoginResultType $loginType,
        bool $trustDevice = false
    ): RedirectResponse {
        $session = $request->getSession();

        $target = $session->get(
            'security.pending_2fa_target',
            $this->urlGenerator->generate('incp_dashboard')
        );

        $session->remove('security.totp_pending');
        $session->remove('security.pending_2fa_target');

        /** @var User|null $user */
        $user = $this->security->getUser();

        $response = new RedirectResponse($target);

        if ($trustDevice && $user instanceof User) {
            $response->headers->setCookie(
                $this->trustedDeviceManager->create($user, $request)
            );
        }

        if ($user instanceof User) {
            $this->loginSuccessRecorder->record(
                $user,
                $request,
                $loginType
            );
        }

        return $response;
    }
}
