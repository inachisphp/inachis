<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Security\Authentication;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\User\User;
use Inachis\Entity\User\UserRecoveryCode;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Manages TOTP recovery codes.
 *
 * Recovery codes are generated once, shown once,
 * stored only as password hashes, and each may be
 * used a single time.
 */
class RecoveryCodeManager
{
    /**
     * Characters excluding ambiguous ones:
     * O, 0, I, 1
     */
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * Number of recovery codes to generate.
     */
    private const CODE_COUNT = 10;

    /**
     * Number of characters before the hyphen.
     */
    private const CODE_PART_LENGTH = 4;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    /**
     * Generates a fresh set of recovery codes.
     *
     * Existing recovery codes are removed.
     *
     * Returns the plaintext codes so they can
     * be displayed once to the user.
     *
     * @param User $user
     *
     * @return string[]
     */
    public function generate(User $user): array
    {
        foreach ($user->getRecoveryCodes() as $existing) {
            $this->entityManager->remove($existing);
        }

        $codes = [];

        for ($i = 0; $i < self::CODE_COUNT; $i++) {
            $plainCode = $this->generateCode();

            $recoveryCode = new UserRecoveryCode();
            $recoveryCode->setUser($user);
            $recoveryCode->setCodeHash(
                password_hash(
                    $plainCode,
                    PASSWORD_ARGON2ID
                )
            );

            $this->entityManager->persist($recoveryCode);
            $codes[] = $plainCode;
        }

        $this->entityManager->flush();

        return $codes;
    }

    /**
     * Verifies and consumes a recovery code.
     *
     * Returns true if the code was valid.
     *
     * @param User $user
     * @param string $code
     *
     * @return bool
     */
    public function verify(
        User $user,
        string $code
    ): bool {
        $code = strtoupper(trim($code));

        foreach ($user->getRecoveryCodes() as $recoveryCode) {
            if ($recoveryCode->isUsed()) {
                continue;
            }

            if (password_verify($code, $recoveryCode->getCodeHash())) {
                $recoveryCode->setUsedAt(
                    new DateTimeImmutable()
                );
                $this->entityManager->flush();

                return true;
            }
        }

        return false;
    }

    /**
     * Returns the number of unused recovery codes.
     *
     * @param User $user
     *
     * @return int
     */
    public function getRemainingCount(
        User $user
    ): int {
        $count = 0;

        foreach ($user->getRecoveryCodes() as $code) {
            if (!$code->isUsed()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Generates a single recovery code.
     *
     * Example:
     *
     * ABCD-EFGH
     *
     * @return string
     */
    private function generateCode(): string
    {
        return sprintf(
            '%s-%s',
            $this->randomPart(),
            $this->randomPart()
        );
    }

    /**
     * Generates one 4-character segment.
     *
     * @return string
     */
    private function randomPart(): string
    {
        $result = '';
        $max = strlen(self::ALPHABET) - 1;

        for ($i = 0; $i < self::CODE_PART_LENGTH; $i++) {
            $result .= self::ALPHABET[random_int(0, $max)];
        }

        return $result;
    }
}
