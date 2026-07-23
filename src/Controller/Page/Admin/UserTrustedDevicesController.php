<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Admin;

use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\User\UserTrustedDevice;
use Inachis\Security\Authentication\TrustedDeviceManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserTrustedDevicesController extends AbstractInachisController
{
    #[Route(
        '/incp/trusted-devices/{deviceId}/rename',
        name: 'incp_security_trusted_device_rename',
        methods: ['POST']
    )]
    public function renameTrustedDevice(
        Request $request,
        UserTrustedDevice $device,
        TrustedDeviceManager $trustedDeviceManager,
    ): Response {
        $user = $this->getCurrentUser();
        if ($device->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $displayName = trim(
            (string) $request->request->get('display_name')
        );

        if ($displayName === '') {
            $this->addFlash('error', 'Device name cannot be empty.');
            return $this->redirectToRoute('incp_admin_edit', [
                'id' => $this->getCurrentUser()->getUsername()
            ]);
        }

        if (mb_strlen($displayName) > 100) {
            $displayName = mb_substr(
                $displayName,
                0,
                100
            );
        }

        $device->setDisplayName($displayName);

        $this->entityManager->flush();

        $this->addFlash('success', 'Trusted device renamed.');

        return $this->redirectToRoute('incp_admin_edit', [
            'id' => $this->getCurrentUser()->getUsername()
        ]);
    }

    #[Route(
        '/incp/admin/trusted-devices/{id}/remove',
        name: 'incp_security_trusted_device_remove',
        methods: ['POST']
    )]
    public function removeTrustedDevice(
        Request $request,
        UserTrustedDevice $device,
        TrustedDeviceManager $trustedDeviceManager,
    ): Response {
        $user = $this->getCurrentUser();
        if ($device->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $currentDevice = $trustedDeviceManager
            ->getCurrentTrustedDevice(
                $user,
                $request
            );

        $trustedDeviceManager->remove($device);

        $response = $this->redirectToRoute('incp_admin_edit', [
            'id' => $this->getCurrentUser()->getUsername()
        ]);

        if (
            $currentDevice !== null &&
            $currentDevice->getId()->equals($device->getId())
        ) {
            $response->headers->setCookie(
                $trustedDeviceManager->clearCookie()
            );
        }

        $this->addFlash('success', 'Trusted device removed.');

        return $response;
    }

    #[Route(
        '/incp/admin/trusted-devices/remove-all',
        name: 'incp_security_trusted_device_remove_all',
        methods: ['POST']
    )]
    public function removeAllTrustedDevices(
        TrustedDeviceManager $trustedDeviceManager,
    ): Response {
        $trustedDeviceManager->removeAll(
            $this->getCurrentUser()
        );

        $response = $this->redirectToRoute('incp_admin_edit', [
            'id' => $this->getCurrentUser()->getUsername()
        ]);

        $response->headers->setCookie(
            $trustedDeviceManager->clearCookie()
        );

        $this->addFlash('success', 'All trusted devices have been removed.');

        return $response;
    }
}
