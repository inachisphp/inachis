<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Page\Security;

use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\System\Setting;
use Inachis\Entity\User\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class PrivacyController extends AbstractInachisController
{
    /**
     * Key prefix for privacy settings stored in database.
     */
    private const SETTING_PREFIX = 'gdpr_';

    /**
     * Renders the Privacy & GDPR management page.
     */
    #[Route('/incp/security/privacy', name: 'incp_security_privacy', methods: ['GET'])]
    public function index(): Response
    {
        // if (!$this->security->isGranted('PRIVACY_GDPR', 'VIEW')) {
        //     throw new AccessDeniedException('Access denied.');
        // }

        $this->viewModel->page->title = 'Privacy & GDPR Management';
        $this->viewModel->page->tab = 'security';

        return $this->render('inadmin/page/security/gdpr.html.twig', [
            'viewModel' => $this->viewModel,
            'privacy' => $this->getPrivacySettings(),
        ]);
    }

    /**
     * Handles saving Privacy & GDPR configuration.
     */
    #[Route('/incp/security/privacy/save', name: 'incp_security_privacy_save', methods: ['POST'])]
    public function save(Request $request): Response
    {
        if (!$this->security->isGranted('PRIVACY_GDPR', 'EDIT')) {
            throw new AccessDeniedException('Access denied.');
        }

        if (!$this->isCsrfTokenValid('privacy_save', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');

            return $this->redirectToRoute('incp_security_privacy');
        }

        $gdprData = $request->request->all('gdpr');
        $settingRepo = $this->entityManager->getRepository(Setting::class);

        $allowedSettings = [
            'banner_enabled' => isset($gdprData['banner_enabled']) ? '1' : '0',
            'consent_mode' => $gdprData['consent_mode'] ?? 'opt_in',
            'banner_message' => trim((string) ($gdprData['banner_message'] ?? '')),
            'anonymize_ips' => $gdprData['anonymize_ips'] ?? '1',
            'log_retention_days' => (string) max(7, (int) ($gdprData['log_retention_days'] ?? 90)),
        ];

        foreach ($allowedSettings as $key => $value) {
            $settingName = self::SETTING_PREFIX.$key;

            $setting = $settingRepo->findOneBy(['name' => $settingName]);
            if (!$setting) {
                $setting = new Setting();
                $setting->setName($settingName);
            }
            $setting->setValue($value);
            $this->entityManager->persist($setting);
        }

        $this->entityManager->flush();
        $this->addFlash('success', 'Privacy and GDPR settings updated successfully.');

        return $this->redirectToRoute('incp_security_privacy');
    }

    /**
     * Handles Subject Access Request (SAR) Personal Data Export.
     */
    #[Route('/incp/security/privacy/export-user', name: 'incp_security_privacy_export_user', methods: ['POST'])]
    public function exportUserData(Request $request): Response
    {
        if (!$this->security->isGranted('PRIVACY_GDPR', 'VIEW')) {
            throw new AccessDeniedException('Access denied.');
        }

        if (!$this->isCsrfTokenValid('sar_export', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');

            return $this->redirectToRoute('incp_security_privacy');
        }

        $emailOrUsername = trim((string) $request->request->get('user_identifier'));
        if (empty($emailOrUsername)) {
            $this->addFlash('error', 'User identifier is required for export.');

            return $this->redirectToRoute('incp_security_privacy');
        }

        $userRepo = $this->entityManager->getRepository(User::class);
        $user = $userRepo->findOneBy(['email' => $emailOrUsername])
             ?? $userRepo->findOneBy(['username' => $emailOrUsername]);

        if (!$user instanceof User) {
            $this->addFlash('error', sprintf('User "%s" not found.', $emailOrUsername));

            return $this->redirectToRoute('incp_security_privacy');
        }

        $userData = [
            'export_date' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'displayName' => $user->getDisplayName(),
                'createdAt' => $user->getPostDate()?->format(\DateTimeInterface::ATOM),
            ],
        ];

        $response = new JsonResponse($userData);
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="sar_export_%s.json"', $user->getUsername()));

        return $response;
    }

    /**
     * Handles Right to be Forgotten / User Anonymization.
     */
    #[Route('/incp/security/privacy/anonymize-user', name: 'incp_security_privacy_anonymize_user', methods: ['POST'])]
    public function anonymizeUser(Request $request): Response
    {
        if (!$this->security->isGranted('PRIVACY_GDPR', 'DELETE')) {
            throw new AccessDeniedException('Access denied.');
        }

        if (!$this->isCsrfTokenValid('anonymize_user', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');

            return $this->redirectToRoute('incp_security_privacy');
        }

        $emailOrUsername = trim((string) $request->request->get('user_identifier'));
        $userRepo = $this->entityManager->getRepository(User::class);
        $user = $userRepo->findOneBy(['email' => $emailOrUsername])
             ?? $userRepo->findOneBy(['username' => $emailOrUsername]);

        if (!$user instanceof User) {
            $this->addFlash('error', sprintf('User "%s" not found.', $emailOrUsername));

            return $this->redirectToRoute('incp_security_privacy');
        }

        $anonymizedHash = substr(md5(uniqid((string) rand(), true)), 0, 10);
        $user->setEmail(sprintf('deleted_%s@anonymized.invalid', $anonymizedHash));
        $user->setUsername(sprintf('deleted_user_%s', $anonymizedHash));
        $user->setDisplayName('Anonymized User');
        $user->setDisabled(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('User "%s" has been anonymized successfully.', $emailOrUsername));

        return $this->redirectToRoute('incp_security_privacy');
    }

    /**
     * Fetches all privacy settings into an associative array with fallback defaults.
     *
     * @return array<string, string>
     */
    private function getPrivacySettings(): array
    {
        $defaults = [
            'banner_enabled' => '1',
            'consent_mode' => 'opt_in',
            'banner_message' => 'We use cookies to improve your browsing experience and analyze site traffic.',
            'anonymize_ips' => '1',
            'log_retention_days' => '90',
        ];

        $settingRepo = $this->entityManager->getRepository(Setting::class);
        $settings = [];

        foreach ($defaults as $key => $defaultValue) {
            $settingName = self::SETTING_PREFIX.$key;
            $setting = $settingRepo->findOneBy(['name' => $settingName]);
            $settings[$key] = $setting ? $setting->getValue() : $defaultValue;
        }

        return $settings;
    }
}
