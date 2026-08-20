<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Security\Authentication;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\User\User;
use Inachis\Entity\User\UserTotp;
use Inachis\Service\QrCode\QrCodeService;

/**
 * Handles the lifecycle of user TOTP authentication.
 */
class TotpManager
{
    public function __construct(
        private readonly TotpService $totpService,
        private readonly QrCodeService $qrCodeService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Begin TOTP setup.
     *
     * @return array{
     *     secret:string,
     *     uri:string,
     *     qrCode:string
     * }
     */
    public function beginSetup(
        User $user,
    ): array {
        $secret = $this->totpService->generateSecret();
        $uri = $this->totpService->getProvisioningUri(
            $user->getUsername(),
            $secret,
        );

        return [
            'secret' => $secret,
            'uri' => $uri,
            'qrCode' => $this->qrCodeService->generate($uri),
        ];
    }

    /**
     * Confirm and enable TOTP.
     */
    public function confirmSetup(
        User $user,
        string $secret,
        string $code,
    ): bool {
        if (!$this->totpService->verifyCode(
            $secret,
            $code,
        )) {
            return false;
        }

        $totp = new UserTotp();
        $totp->setUser($user);

        /*
         * Stored as plaintext only in memory.
         * UserTotpListener encrypts this before persistence.
         */
        $totp->setSecret($secret);
        $totp->setEnabledAt(
            new \DateTimeImmutable(),
        );

        $this->entityManager->persist($totp);
        $this->entityManager->flush();

        return true;
    }

    /**
     * Verify a TOTP code.
     */
    public function verify(User $user, string $code): bool
    {
        $totp = $user->getTotp();
        if (null === $totp) {
            return false;
        }
        if (null === $totp->getEnabledAt()) {
            return false;
        }

        $secret = $totp->getSecret();
        if (null === $secret) {
            return false;
        }

        $valid = $this->totpService->verifyCode(
            $secret,
            $code,
        );

        if ($valid) {
            $totp->setLastUsedAt(new \DateTimeImmutable());

            $this->entityManager->flush();
        }

        return $valid;
    }

    /**
     * Disable TOTP.
     */
    public function disable(
        User $user,
    ): void {
        $totp = $user->getTotp();
        if (null === $totp) {
            return;
        }

        $this->entityManager->remove($totp);
        $this->entityManager->flush();
    }
}
