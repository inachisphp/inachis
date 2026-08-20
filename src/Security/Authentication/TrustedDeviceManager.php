<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Security\Authentication;

use DeviceDetector\DeviceDetector;
use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\User\User;
use Inachis\Entity\User\UserTrustedDevice;
use Inachis\Repository\User\UserTrustedDeviceRepository;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

class TrustedDeviceManager
{
    private const COOKIE_NAME = 'inachis_trusted_device';

    private const VALIDITY_INTERVAL = 'P30D';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserTrustedDeviceRepository $userTrustedDeviceRepository,
    ) {
    }

    /**
     * Creates a trusted device and returns the cookie that should
     * be attached to the response.
     */
    public function create(
        User $user,
        Request $request,
    ): Cookie {
        $selector = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));

        $trustedDevice = new UserTrustedDevice();

        $trustedDevice
            ->setUser($user)
            ->setSelector($selector)
            ->setValidatorHash(
                hash('sha256', $validator),
            )
            ->setDisplayName(
                $this->guessDisplayName($request),
            )
            ->setLastIp(
                $request->getClientIp(),
            )
            ->setLastUserAgent(
                $request->headers->get('User-Agent'),
            )
            ->setExpiresAt(
                (new \DateTimeImmutable())->add(
                    new \DateInterval(self::VALIDITY_INTERVAL),
                ),
            );

        $this->entityManager->persist($trustedDevice);
        $this->entityManager->flush();

        return $this->createCookie(
            $selector,
            $validator,
            $trustedDevice->getExpiresAt(),
        );
    }

    /**
     * Returns the current trusted device.
     */
    public function getCurrentTrustedDevice(
        User $user,
        Request $request,
    ): ?UserTrustedDevice {
        $cookie = $request->cookies->get(
            self::COOKIE_NAME,
        );
        if (null === $cookie) {
            return null;
        }

        $parts = explode(':', $cookie, 2);
        if (2 !== count($parts)) {
            return null;
        }

        [$selector, $validator] = $parts;

        $device = $this->userTrustedDeviceRepository
            ->findBySelector(
                $user,
                $selector,
            );
        if (null === $device) {
            return null;
        }

        if (!hash_equals(
            $device->getValidatorHash(),
            hash('sha256', $validator),
        )) {
            return null;
        }

        return $device;
    }

    /**
     * Returns all active trusted devices.
     *
     * @return UserTrustedDevice[]
     */
    public function getTrustedDevices(
        User $user,
    ): array {
        $this->userTrustedDeviceRepository->removeExpiredDevices($user);

        return $this->userTrustedDeviceRepository->getTrustedDevices($user);
    }

    /**
     * Validates the trusted-device cookie.
     *
     * Returns a refreshed cookie if valid,
     * otherwise null.
     */
    public function validate(User $user, Request $request): ?Cookie
    {
        $this->userTrustedDeviceRepository->removeExpiredDevices($user);

        $cookie = $request->cookies->get(self::COOKIE_NAME);
        if (null === $cookie) {
            return null;
        }

        $parts = explode(':', $cookie, 2);
        if (2 !== count($parts)) {
            return null;
        }

        [$selector, $validator] = $parts;

        $device = $this->userTrustedDeviceRepository
            ->findBySelector($user, $selector);
        if (null === $device) {
            return null;
        }

        if (
            !hash_equals(
                $device->getValidatorHash(),
                hash('sha256', $validator),
            )
        ) {
            return null;
        }

        $expiresAt = (new \DateTimeImmutable())->add(
            new \DateInterval(self::VALIDITY_INTERVAL),
        );

        $device
            ->setExpiresAt($expiresAt)
            ->setLastIp($request->getClientIp())
            ->setLastUsedAt(new \DateTimeImmutable())
            ->setLastUserAgent($request->headers->get('User-Agent'));

        $this->entityManager->flush();

        return $this->createCookie(
            $selector,
            $validator,
            $expiresAt,
        );
    }

    /**
     * Removes a trusted device.
     */
    public function remove(UserTrustedDevice $device): void
    {
        $this->entityManager->remove($device);
        $this->entityManager->flush();
    }

    /**
     * Removes all trusted devices belonging to a user.
     */
    public function removeAll(User $user): void
    {
        $this->userTrustedDeviceRepository->removeAll($user);
    }

    /**
     * Returns an expired cookie that removes the browser cookie.
     */
    public function clearCookie(): Cookie
    {
        return Cookie::create(
            self::COOKIE_NAME,
            '',
            new \DateTimeImmutable('-1 day'),
            '/',
            null,
            true,
            true,
            false,
            Cookie::SAMESITE_LAX,
        );
    }

    /**
     * Creates the trusted-device cookie.
     */
    private function createCookie(
        string $selector,
        string $validator,
        \DateTimeImmutable $expires,
    ): Cookie {
        return Cookie::create(
            self::COOKIE_NAME,
            $selector.':'.$validator,
            $expires,
            '/',
            null,
            true,
            true,
            false,
            Cookie::SAMESITE_LAX,
        );
    }

    /**
     * Generates a display name such as "Chrome on Windows".
     */
    private function guessDisplayName(Request $request): string
    {
        $userAgent = $request->headers->get('User-Agent');
        if (!$userAgent) {
            return 'Unknown Device';
        }

        $dd = new DeviceDetector($userAgent);
        $dd->parse();
        if ($dd->isBot()) {
            $bot = $dd->getBot();

            return $bot['name'] ?? 'Bot';
        }

        $client = $dd->getClient();
        $os = $dd->getOs();

        $browser = $client['name'] ?? 'Unknown Browser';
        $platform = $os['name'] ?? 'Unknown OS';

        if ('Unknown Browser' === $browser && 'Unknown OS' === $platform) {
            return 'Unknown Device';
        }

        return sprintf('%s on %s', $browser, $platform);
    }
}
